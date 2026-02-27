<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Executive\ControlTowerResource;
use App\Services\Executive\ControlTowerService;
use Illuminate\Http\JsonResponse;

class ExecutiveControlTowerController extends Controller
{
    /**
     * GET /api/v1/executive/control-tower
     *
     * Returns a unified governance intelligence payload aggregated from all
     * platform subsystems. Read-only, cached 60 s, never throws 500.
     */
    public function show(ControlTowerService $service): JsonResponse
    {
        return (new ControlTowerResource($service->build()))
            ->additional(['meta' => ['generated_at' => now()->toIso8601String()]])
            ->response();
    }
}
