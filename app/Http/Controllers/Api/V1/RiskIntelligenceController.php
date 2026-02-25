<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Risk\RiskIntelligenceResource;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Http\JsonResponse;

class RiskIntelligenceController extends Controller
{
    public function __construct(private readonly RiskIntelligenceService $service) {}

    public function show(string $countryId): JsonResponse
    {
        $summary = $this->service->getNationalRiskSummary($countryId);

        return response()->json(['data' => new RiskIntelligenceResource($summary)]);
    }
}
