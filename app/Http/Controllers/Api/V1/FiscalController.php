<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Fiscal\BudgetAllocationResource;
use App\Http\Resources\Fiscal\DebtRecordResource;
use App\Http\Resources\Fiscal\FiscalRiskSignalResource;
use App\Http\Resources\Fiscal\RevenueRecordResource;
use App\Services\Fiscal\BudgetTrackerService;
use App\Services\Fiscal\DebtMonitorService;
use App\Services\Fiscal\FiscalRiskSignalService;
use App\Services\Fiscal\RevenueStabilityService;
use Illuminate\Http\JsonResponse;

class FiscalController extends Controller
{
    public function __construct(
        private readonly DebtMonitorService $debtService,
        private readonly BudgetTrackerService $budgetService,
        private readonly RevenueStabilityService $revenueService,
        private readonly FiscalRiskSignalService $riskSignalService,
    ) {}

    public function debt(string $countryId, int $year): JsonResponse
    {
        $record = $this->debtService->getDebtRecord($countryId, $year);

        if (!$record) {
            return response()->json(['message' => 'No debt record found.'], 404);
        }

        return response()->json(['data' => new DebtRecordResource($record)]);
    }

    public function budget(string $countryId, int $year): JsonResponse
    {
        $allocations = $this->budgetService->getAllocations($countryId, $year);

        return response()->json(['data' => BudgetAllocationResource::collection($allocations)]);
    }

    public function revenue(string $countryId, int $year): JsonResponse
    {
        $record = $this->revenueService->getRevenueRecord($countryId, $year);

        if (!$record) {
            return response()->json(['message' => 'No revenue record found.'], 404);
        }

        return response()->json(['data' => new RevenueRecordResource($record)]);
    }

    public function risk(string $countryId, int $year): JsonResponse
    {
        $signals = $this->riskSignalService->getSignals($countryId, $year);

        return response()->json(['data' => FiscalRiskSignalResource::collection($signals)]);
    }
}
