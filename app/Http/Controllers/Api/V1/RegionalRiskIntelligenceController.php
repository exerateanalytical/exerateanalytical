<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Risk\RegionalRiskDetailResource;
use App\Http\Resources\Risk\RegionalRiskRankingResource;
use App\Services\Risk\RegionalRiskIntelligenceService;
use Illuminate\Http\JsonResponse;

class RegionalRiskIntelligenceController extends Controller
{
    public function __construct(private readonly RegionalRiskIntelligenceService $service) {}

    public function ranking(string $countryId): JsonResponse
    {
        $data = $this->service->getRegionalRiskRanking($countryId);

        return response()->json([
            'data' => RegionalRiskRankingResource::collection(collect($data)),
        ]);
    }

    public function detail(string $countryId, string $regionId): JsonResponse
    {
        $data = $this->service->getRegionalRiskDetail($countryId, $regionId);

        return response()->json(['data' => new RegionalRiskDetailResource($data)]);
    }
}
