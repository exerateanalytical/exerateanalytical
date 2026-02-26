<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Risk\RiskGovernanceMetricsService;
use Illuminate\Http\JsonResponse;

class ExecutiveGovernanceMetricsController extends Controller
{
    public function __construct(
        private readonly RiskGovernanceMetricsService $governanceMetricsService,
    ) {}

    public function metrics(string $country): JsonResponse
    {
        return response()->json([
            'country_id' => $country,
            'metrics'    => $this->governanceMetricsService->metrics($country),
        ]);
    }
}
