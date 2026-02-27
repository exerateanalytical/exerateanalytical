<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Executive\GovernanceActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GovernanceActionController extends Controller
{
    /**
     * POST /api/v1/internal/governance/actions
     *
     * SuperAdmin-only. Validates, dispatches, and persists a governance action.
     */
    public function store(Request $request, GovernanceActionService $service): JsonResponse
    {
        abort_if(! $request->user()->hasRole('SuperAdmin'), 403);

        $validated = $request->validate([
            'action_type' => [
                'required',
                'string',
                Rule::in([
                    'simulate_contagion',
                    'simulate_shock',
                    'launch_poll',
                    'open_consultation',
                    'flag_region',
                ]),
            ],
            'payload'     => ['sometimes', 'array'],
        ]);

        $action = $service->execute(
            $request->user(),
            $validated['action_type'],
            $validated['payload'] ?? [],
        );

        return response()->json($action, 201);
    }
}
