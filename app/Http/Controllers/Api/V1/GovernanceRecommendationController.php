<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GovernanceRecommendation;
use App\Services\Governance\GovernanceWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class GovernanceRecommendationController extends Controller
{
    /**
     * GET /api/v1/executive/recommendations
     *
     * Returns all currently active recommendations ordered by severity descending
     * (critical → high → medium → low). No authentication required — read-only.
     */
    public function index(): JsonResponse
    {
        $recommendations = GovernanceRecommendation::where('status', 'active')
            ->orderByRaw(GovernanceRecommendation::severityWeight() . ' DESC')
            ->orderByDesc('confidence_score')
            ->get();

        return response()->json(['data' => $recommendations]);
    }

    /**
     * POST /api/v1/executive/recommendations/{recommendation}/accept
     *
     * SuperAdmin or Governor only. Creates a governance action proposal from
     * the recommendation's suggested_action and links it back via accepted_action_id.
     * Status transitions to 'accepted'.
     *
     * Safety: this creates a PROPOSAL, not an execution. Human approval is
     * still required to execute.
     */
    public function accept(
        Request $request,
        GovernanceRecommendation $recommendation,
        GovernanceWorkflowService $workflow,
    ): JsonResponse {
        abort_if(
            ! $request->user()->hasRole('SuperAdmin') && ! $request->user()->hasRole('Governor'),
            403
        );

        if ($recommendation->status !== 'active') {
            return response()->json([
                'message' => "Cannot accept recommendation with status '{$recommendation->status}'.",
            ], 422);
        }

        $suggested = $recommendation->suggested_action ?? [];

        try {
            $action = $workflow->proposeAction(
                $request->user(),
                $suggested['action_type'] ?? '',
                $suggested['payload'] ?? [],
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $recommendation->accepted_action_id = $action->id;
        $recommendation->status             = 'accepted';
        $recommendation->save();

        return response()->json([
            'recommendation' => $recommendation->fresh(),
            'action'         => $action,
        ]);
    }

    /**
     * POST /api/v1/executive/recommendations/{recommendation}/dismiss
     *
     * SuperAdmin or Governor only. Marks the recommendation as dismissed.
     * It will not reappear until the next scheduler cycle detects the same signal.
     */
    public function dismiss(Request $request, GovernanceRecommendation $recommendation): JsonResponse
    {
        abort_if(
            ! $request->user()->hasRole('SuperAdmin') && ! $request->user()->hasRole('Governor'),
            403
        );

        if ($recommendation->status !== 'active') {
            return response()->json([
                'message' => "Cannot dismiss recommendation with status '{$recommendation->status}'.",
            ], 422);
        }

        $recommendation->status = 'dismissed';
        $recommendation->save();

        return response()->json(['message' => 'Recommendation dismissed.']);
    }
}
