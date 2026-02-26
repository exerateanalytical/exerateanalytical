<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Http\JsonResponse;

class ExecutiveBriefController extends Controller
{
    public function __construct(
        private readonly RiskIntelligenceService $riskService,
    ) {}

    public function show(string $country): JsonResponse
    {
        $summary = $this->riskService->getNationalRiskSummary($country);

        return response()->json([
            'country_id'       => $country,
            'strategic_brief'  => $this->riskService->buildStrategicBrief($summary),
            'last_updated'     => $summary['last_updated'],
        ]);
    }
}
