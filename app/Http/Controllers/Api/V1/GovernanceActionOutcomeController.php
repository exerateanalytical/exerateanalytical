<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Governance\GovernanceActionOutcomeResource;
use App\Models\GovernanceAction;
use App\Models\GovernanceActionOutcome;
use Illuminate\Http\JsonResponse;

/**
 * CT-14 — Action outcome history endpoint.
 *
 * Returns measured effectiveness rows for a governance action,
 * ordered by observation window (24h → 7d → 30d).
 */
class GovernanceActionOutcomeController extends Controller
{
    private const WINDOW_ORDER = ['24h' => 1, '7d' => 2, '30d' => 3];

    /**
     * GET /api/v1/executive/actions/{action}/outcomes
     */
    public function index(GovernanceAction $action): JsonResponse
    {
        $outcomes = GovernanceActionOutcome::where('governance_action_id', $action->id)
            ->get()
            ->sortBy(fn ($row) => self::WINDOW_ORDER[$row->observation_window] ?? 99)
            ->values();

        return response()->json([
            'data' => GovernanceActionOutcomeResource::collection($outcomes),
            'meta' => [
                'governance_action_id' => $action->id,
                'action_type'          => $action->action_type,
                'executed_at'          => $action->executed_at?->toIso8601String(),
                'windows_measured'     => $outcomes->pluck('observation_window')->all(),
            ],
        ]);
    }
}
