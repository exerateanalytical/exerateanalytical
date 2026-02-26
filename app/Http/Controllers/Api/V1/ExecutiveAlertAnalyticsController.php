<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Risk\RiskAlertAnalyticsService;
use Illuminate\Http\JsonResponse;

class ExecutiveAlertAnalyticsController extends Controller
{
    public function __construct(
        private readonly RiskAlertAnalyticsService $analyticsService,
    ) {}

    public function metrics(string $country): JsonResponse
    {
        return response()->json([
            'country_id' => $country,
            'metrics'    => $this->analyticsService->metrics($country),
        ]);
    }
}
