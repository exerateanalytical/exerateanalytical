<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Fiscal\DebtRecordResource;
use App\Http\Resources\Fiscal\BudgetAllocationResource;
use App\Http\Resources\Fiscal\RevenueRecordResource;
use App\Models\BudgetAllocation;
use App\Models\FiscalRiskSignal;
use App\Models\NationalDebtRecord;
use App\Models\RevenueRecord;
use App\Services\Fiscal\BudgetTrackerService;
use App\Services\Fiscal\DebtMonitorService;
use App\Services\Fiscal\RevenueStabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class FiscalController extends Controller
{
    public function __construct(
        private readonly DebtMonitorService $debtService,
        private readonly BudgetTrackerService $budgetService,
        private readonly RevenueStabilityService $revenueService,
    ) {}

    public function debt(string $countryId, int $year): JsonResponse
    {
        $record = Cache::remember("fiscal:debt:{$countryId}:{$year}", 7200, function () use ($countryId, $year) {
            return NationalDebtRecord::where('country_id', $countryId)
                ->where('year', $year)
                ->latest()
                ->first();
        });

        if (!$record) {
            return response()->json(['message' => 'No debt record found.'], 404);
        }

        return response()->json(['data' => new DebtRecordResource($record)]);
    }

    public function budget(string $countryId, int $year): JsonResponse
    {
        $allocations = Cache::remember("fiscal:budget:{$countryId}:{$year}", 7200, function () use ($countryId, $year) {
            return BudgetAllocation::where('country_id', $countryId)
                ->where('year', $year)
                ->get();
        });

        return response()->json(['data' => BudgetAllocationResource::collection($allocations)]);
    }

    public function revenue(string $countryId, int $year): JsonResponse
    {
        $record = Cache::remember("fiscal:revenue:{$countryId}:{$year}", 7200, function () use ($countryId, $year) {
            return RevenueRecord::where('country_id', $countryId)
                ->where('year', $year)
                ->latest()
                ->first();
        });

        if (!$record) {
            return response()->json(['message' => 'No revenue record found.'], 404);
        }

        return response()->json(['data' => new RevenueRecordResource($record)]);
    }

    public function risk(string $countryId, int $year): JsonResponse
    {
        $signals = FiscalRiskSignal::where('country_id', $countryId)
            ->where('year', $year)
            ->orderBy('triggered_at', 'desc')
            ->get();

        return response()->json(['data' => $signals]);
    }
}
