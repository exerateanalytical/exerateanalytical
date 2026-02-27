<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Risk\RiskContagionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiskContagionController extends Controller
{
    public function __construct(
        private readonly RiskContagionService $service,
    ) {}

    public function execute(Request $request): JsonResponse
    {
        abort_if(! $request->user()->hasRole('SuperAdmin'), 403);

        $validated = $request->validate([
            'region_id'        => ['required', 'uuid'],
            'shock_magnitude'  => ['required', 'numeric', 'min:0', 'max:100'],
            'contagion_factor' => ['sometimes', 'numeric', 'min:0', 'max:0.7'],
            'iterations'       => ['sometimes', 'integer', 'min:1', 'max:8'],
        ]);

        $run = $this->service->execute(
            $validated['region_id'],
            (float) $validated['shock_magnitude'],
            (float) ($validated['contagion_factor'] ?? 0.5),
            (int)   ($validated['iterations']       ?? 5),
        );

        return response()->json($run, 201);
    }
}
