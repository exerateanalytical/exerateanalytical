<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\AlertWatchlistResource;
use App\Http\Resources\Dashboard\RiskDriversResource;
use App\Http\Resources\Dashboard\RiskHistoryResource;
use App\Http\Resources\Dashboard\RiskRankingResource;
use App\Services\Dashboard\ExecutiveRiskDashboardService;
use Illuminate\Http\JsonResponse;

class ExecutiveRiskDashboardController extends Controller
{
    public function __construct(private readonly ExecutiveRiskDashboardService $service) {}

    public function riskRanking(): JsonResponse
    {
        $ranking = $this->service->getRiskRanking();

        return response()->json(['data' => RiskRankingResource::collection($ranking)]);
    }

    public function riskDrivers(string $countryId): JsonResponse
    {
        $drivers = $this->service->getRiskDrivers($countryId);

        return response()->json(['data' => new RiskDriversResource($drivers)]);
    }

    public function riskHistory(string $countryId): JsonResponse
    {
        $history = $this->service->getRiskHistory($countryId);

        return response()->json(['data' => RiskHistoryResource::collection($history)]);
    }

    public function alertWatchlist(): JsonResponse
    {
        $watchlist = $this->service->getAlertWatchlist();

        return response()->json(['data' => AlertWatchlistResource::collection($watchlist)]);
    }
}
