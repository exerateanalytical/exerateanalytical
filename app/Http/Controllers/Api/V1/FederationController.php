<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Federation\FederationAggregationService;
use App\Services\Federation\RegionalSystemicSnapshotService;
use Illuminate\Http\JsonResponse;

class FederationController extends Controller
{
    public function __construct(
        private readonly FederationAggregationService $aggregationService,
        private readonly RegionalSystemicSnapshotService $regionalService,
    ) {}

    /**
     * GET /api/v1/federation/global
     *
     * Returns the latest global federated snapshot.
     */
    public function global(): JsonResponse
    {
        $snapshot = $this->aggregationService->latestGlobal();

        if ($snapshot === null) {
            return response()->json(['message' => 'No global snapshot available yet.'], 404);
        }

        return response()->json($snapshot);
    }

    /**
     * GET /api/v1/federation/regions
     *
     * Returns the latest snapshot for every federation region.
     */
    public function regions(): JsonResponse
    {
        $snapshots = $this->regionalService->allRegionsLatest();

        return response()->json($snapshots->values());
    }
}
