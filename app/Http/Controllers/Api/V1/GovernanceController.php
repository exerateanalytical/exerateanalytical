<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Governance\GovernanceScoreResource;
use App\Models\Country;
use App\Models\Region;
use App\Services\Governance\GovernanceIndexService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GovernanceController extends Controller
{
    public function __construct(private readonly GovernanceIndexService $service) {}

    public function show(string $countryId, int $year): GovernanceScoreResource
    {
        $score = Cache::remember("governance:{$countryId}:{$year}", 3600, function () use ($countryId, $year) {
            return \App\Models\GovernanceScore::where('country_id', $countryId)
                ->where('year', $year)
                ->whereNull('region_id')
                ->latest('calculated_at')
                ->firstOrFail();
        });

        return new GovernanceScoreResource($score);
    }

    public function showRegional(string $countryId, string $regionId, int $year): GovernanceScoreResource
    {
        $score = \App\Models\GovernanceScore::where('country_id', $countryId)
            ->where('region_id', $regionId)
            ->where('year', $year)
            ->latest('calculated_at')
            ->firstOrFail();

        return new GovernanceScoreResource($score);
    }

    public function trend(string $countryId): JsonResponse
    {
        $trend = Cache::remember("governance:trend:{$countryId}", 3600, function () use ($countryId) {
            return $this->service->getTrend($countryId);
        });

        return response()->json(['data' => $trend]);
    }

    public function recalculate(Request $request, string $countryId, int $year): JsonResponse
    {
        $regionId = $request->input('region_id');
        $score = $this->service->calculateCompositeScore($countryId, $regionId, $year);

        return response()->json([
            'status' => 'success',
            'message' => 'Governance score recalculated.',
            'data' => new GovernanceScoreResource($score),
        ]);
    }

    public function sensitivity(string $countryId, int $year): JsonResponse
    {
        $results = $this->service->runSensitivitySimulation($countryId, $year);

        return response()->json(['data' => $results]);
    }
}
