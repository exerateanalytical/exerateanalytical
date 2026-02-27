<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GovernanceAction;
use App\Services\Executive\GovernanceActionService;
use App\Services\Governance\GovernanceWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class GovernanceActionController extends Controller
{
    private const ALLOWED_TYPES = [
        'simulate_contagion',
        'simulate_shock',
        'launch_poll',
        'open_consultation',
        'flag_region',
    ];

    /**
     * POST /api/v1/internal/governance/actions
     *
     * SuperAdmin-only. Validates, dispatches, and persists a governance action.
     */
    public function store(Request $request, GovernanceActionService $service): JsonResponse
    {
        abort_if(! $request->user()->hasRole('SuperAdmin'), 403);

        $validated = $request->validate([
            'action_type' => ['required', 'string', Rule::in(self::ALLOWED_TYPES)],
            'payload'     => ['sometimes', 'array'],
        ]);

        $action = $service->execute(
            $request->user(),
            $validated['action_type'],
            $validated['payload'] ?? [],
        );

        return response()->json($action, 201);
    }

    /**
     * POST /api/v1/internal/governance/actions/propose
     *
     * SuperAdmin or Governor. Creates a governance action proposal awaiting approval.
     */
    public function propose(Request $request, GovernanceWorkflowService $workflow): JsonResponse
    {
        abort_if(
            ! $request->user()->hasRole('SuperAdmin') && ! $request->user()->hasRole('Governor'),
            403
        );

        $validated = $request->validate([
            'action_type' => ['required', 'string', Rule::in(self::ALLOWED_TYPES)],
            'payload'     => ['sometimes', 'array'],
        ]);

        $action = $workflow->proposeAction(
            $request->user(),
            $validated['action_type'],
            $validated['payload'] ?? [],
        );

        return response()->json($action, 201);
    }

    /**
     * PATCH /api/v1/internal/governance/actions/{action}/approve
     *
     * SuperAdmin or Governor. Approves a pending proposal and executes it atomically.
     */
    public function approve(Request $request, GovernanceAction $action, GovernanceWorkflowService $workflow): JsonResponse
    {
        abort_if(
            ! $request->user()->hasRole('SuperAdmin') && ! $request->user()->hasRole('Governor'),
            403
        );

        try {
            $executed = $workflow->approveAction($request->user(), $action);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($executed);
    }

    /**
     * PATCH /api/v1/internal/governance/actions/{action}/reject
     *
     * SuperAdmin or Governor. Rejects a pending proposal with a mandatory reason.
     */
    public function reject(Request $request, GovernanceAction $action, GovernanceWorkflowService $workflow): JsonResponse
    {
        abort_if(
            ! $request->user()->hasRole('SuperAdmin') && ! $request->user()->hasRole('Governor'),
            403
        );

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $rejected = $workflow->rejectAction($request->user(), $action, $validated['reason']);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($rejected);
    }
}
