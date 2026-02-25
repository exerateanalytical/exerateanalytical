<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Development\StoreServiceAccessRequest;
use App\Http\Resources\Development\RegionalEquityMetricResource;
use App\Http\Resources\Development\ServiceAccessResource;
use App\Jobs\RegionalEquityCalculationJob;
use App\Models\RegionalEquityMetric;
use App\Models\ServiceAccessRecord;
use App\Services\Development\ServiceAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DevelopmentController extends Controller
{
    public function show(string $countryId, int $year): JsonResponse
    {
        $record = Cache::remember("development:{$countryId}:{$year}", 7200, function () use ($countryId, $year) {
            return ServiceAccessRecord::where('country_id', $countryId)
                ->where('year', $year)
                ->whereNull('region_id')
                ->latest()
                ->first();
        });

        if (!$record) {
            return response()->json(['message' => 'No service access record found.'], 404);
        }

        $equity = RegionalEquityMetric::where('country_id', $countryId)
            ->where('year', $year)
            ->first();

        return response()->json([
            'data' => new ServiceAccessResource($record),
            'equity' => $equity ? new RegionalEquityMetricResource($equity) : null,
        ]);
    }

    public function showRegional(string $countryId, string $regionId, int $year): JsonResponse
    {
        $record = ServiceAccessRecord::where('country_id', $countryId)
            ->where('region_id', $regionId)
            ->where('year', $year)
            ->latest()
            ->first();

        if (!$record) {
            return response()->json(['message' => 'No regional service access record found.'], 404);
        }

        return response()->json(['data' => new ServiceAccessResource($record)]);
    }

    public function trend(string $countryId): JsonResponse
    {
        $records = ServiceAccessRecord::where('country_id', $countryId)
            ->whereNull('region_id')
            ->orderBy('year', 'desc')
            ->limit(5)
            ->get();

        return response()->json(['data' => ServiceAccessResource::collection($records)]);
    }

    public function store(StoreServiceAccessRequest $request, ServiceAccessService $service): JsonResponse
    {
        $this->authorize('store', ServiceAccessRecord::class);

        $record = $service->store($request->validated());

        return response()->json(['data' => new ServiceAccessResource($record)], 201);
    }

    public function recalculate(string $country, int $year): JsonResponse
    {
        $this->authorize('recalculate', ServiceAccessRecord::class);

        RegionalEquityCalculationJob::dispatch($country, $year);

        return response()->json(['message' => 'Equity recalculation queued.'], 202);
    }
}
