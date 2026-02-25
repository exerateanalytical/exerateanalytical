<?php

namespace App\Services\Fiscal;

use App\Exceptions\DataIntegrityException;
use App\Models\BudgetAllocation;
use App\Models\FiscalRiskSignal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BudgetTrackerService
{
    public function calculateExecutionRate(string $allocationId): BudgetAllocation
    {
        $allocation = BudgetAllocation::findOrFail($allocationId);

        if ((float) $allocation->allocated_amount == 0) {
            throw new DataIntegrityException("Allocated amount cannot be zero for execution rate calculation.");
        }

        $rate = ((float) $allocation->executed_amount / (float) $allocation->allocated_amount) * 100;
        $allocation->execution_rate = round($rate, 2);
        $allocation->save();

        Cache::forget("fiscal:budget:{$allocation->country_id}:{$allocation->year}");

        return $allocation;
    }

    public function detectDelay(string $countryId, int $year): int
    {
        $allocations = BudgetAllocation::where('country_id', $countryId)
            ->where('year', $year)
            ->where('execution_rate', '<', 50)
            ->get();

        $flagged = 0;
        foreach ($allocations as $allocation) {
            $allocation->delay_flag = true;
            $allocation->save();
            $flagged++;
        }

        Log::info('Delay detection complete', [
            'country_id' => $countryId,
            'year'       => $year,
            'flagged'    => $flagged,
        ]);

        if ($flagged > 0) {
            FiscalRiskSignal::firstOrCreate(
                [
                    'country_id' => $countryId,
                    'year'       => $year,
                    'risk_type'  => 'budget_execution',
                ],
                [
                    'severity'    => 'moderate',
                    'description' => "{$flagged} budget allocation(s) flagged with execution rate below 50%.",
                    'triggered_at' => now(),
                ]
            );
        }

        return $flagged;
    }

    public function recalculateAllRates(string $countryId, int $year): void
    {
        BudgetAllocation::where('country_id', $countryId)
            ->where('year', $year)
            ->each(function ($allocation) {
                if ((float) $allocation->allocated_amount > 0) {
                    $rate = ((float) $allocation->executed_amount / (float) $allocation->allocated_amount) * 100;
                    $allocation->execution_rate = round($rate, 2);
                    $allocation->save();
                }
            });
    }
}
