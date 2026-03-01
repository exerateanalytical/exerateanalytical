<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Governance\GovernanceAssignmentResource;
use App\Models\GovernanceAction;
use App\Models\GovernanceAssignment;
use App\Services\Governance\GovernanceAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * CT-15 Institutional Coordination Engine — API controller.
 *
 * Mutation routes live under /api/v1/internal/* and require SuperAdmin or
 * Governor. The index read is public (executive namespace) so the Control
 * Tower dashboard can refresh without authentication.
 */
class GovernanceAssignmentController extends Controller
{
    private function authorise(Request $request): void
    {
        abort_if(
            ! $request->user()->hasRole('SuperAdmin') && ! $request->user()->hasRole('Governor'),
            403
        );
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/executive/assignments
     *
     * Returns up to 50 assignments ordered by urgency (blocked → in_progress →
     * assigned → acknowledged → completed) then by recency.
     */
    public function index(): JsonResponse
    {
        $assignments = GovernanceAssignment::with([
            'action:id,action_type',
            'assignedBy:id,name',
        ])
        ->orderByRaw("CASE status
            WHEN 'blocked'      THEN 1
            WHEN 'in_progress'  THEN 2
            WHEN 'assigned'     THEN 3
            WHEN 'acknowledged' THEN 4
            WHEN 'completed'    THEN 5
            ELSE 6 END")
        ->orderByDesc('created_at')
        ->limit(50)
        ->get();

        return response()->json([
            'data' => GovernanceAssignmentResource::collection($assignments),
        ]);
    }

    // ── Mutations (internal / auth required) ─────────────────────────────────

    /**
     * POST /api/v1/internal/governance/actions/{action}/assign
     */
    public function assign(
        Request $request,
        GovernanceAction $action,
        GovernanceAssignmentService $service,
    ): JsonResponse {
        $this->authorise($request);

        $validated = $request->validate([
            'institution_name' => ['required', 'string', 'max:120'],
        ]);

        $assignment = $service->assignAction(
            $action,
            $validated['institution_name'],
            $request->user(),
        );

        $assignment->load(['action:id,action_type', 'assignedBy:id,name']);

        return response()->json(['data' => new GovernanceAssignmentResource($assignment)], 201);
    }

    /**
     * PATCH /api/v1/internal/governance/assignments/{assignment}/acknowledge
     */
    public function acknowledge(
        Request $request,
        GovernanceAssignment $assignment,
        GovernanceAssignmentService $service,
    ): JsonResponse {
        $this->authorise($request);

        $assignment = $service->acknowledge($assignment);
        $assignment->load(['action:id,action_type', 'assignedBy:id,name']);

        return response()->json(['data' => new GovernanceAssignmentResource($assignment)]);
    }

    /**
     * PATCH /api/v1/internal/governance/assignments/{assignment}/progress
     *
     * Accepts:
     *   percent  int (0-100) — required unless blocked=true
     *   notes    string      — optional
     *   blocked  bool        — when true, sets status to blocked regardless of percent
     */
    public function progress(
        Request $request,
        GovernanceAssignment $assignment,
        GovernanceAssignmentService $service,
    ): JsonResponse {
        $this->authorise($request);

        $validated = $request->validate([
            'percent' => ['required_without:blocked', 'nullable', 'integer', 'min:0', 'max:100'],
            'notes'   => ['sometimes', 'nullable', 'string', 'max:2000'],
            'blocked' => ['sometimes', 'boolean'],
        ]);

        $blocked = (bool) ($validated['blocked'] ?? false);
        $percent = (int) ($validated['percent']  ?? $assignment->progress_percent);

        try {
            $assignment = $service->updateProgress(
                $assignment,
                $percent,
                $validated['notes'] ?? null,
                $blocked,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $assignment->load(['action:id,action_type', 'assignedBy:id,name']);

        return response()->json(['data' => new GovernanceAssignmentResource($assignment)]);
    }

    /**
     * PATCH /api/v1/internal/governance/assignments/{assignment}/complete
     */
    public function complete(
        Request $request,
        GovernanceAssignment $assignment,
        GovernanceAssignmentService $service,
    ): JsonResponse {
        $this->authorise($request);

        $assignment = $service->complete($assignment);
        $assignment->load(['action:id,action_type', 'assignedBy:id,name']);

        return response()->json(['data' => new GovernanceAssignmentResource($assignment)]);
    }
}
