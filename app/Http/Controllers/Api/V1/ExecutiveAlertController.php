<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Risk\RiskAlertService;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Http\JsonResponse;

class ExecutiveAlertController extends Controller
{
    public function __construct(
        private readonly RiskIntelligenceService $riskService,
        private readonly RiskAlertService $alertService,
    ) {}

    public function show(string $country): JsonResponse
    {
        $summary = $this->riskService->getNationalRiskSummary($country);
        $alerts  = $this->alertService->evaluate($summary);

        return response()->json([
            'country_id'   => $country,
            'alert_count'  => count($alerts),
            'alerts'       => $alerts,
            'last_updated' => $summary['last_updated'],
        ]);
    }
}
