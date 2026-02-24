<?php

namespace App\Services\Fiscal;

use App\Models\FiscalRiskSignal;
use App\Models\RevenueRecord;
use Illuminate\Support\Facades\Log;

class RevenueStabilityService
{
    private const VOLATILITY_THRESHOLD = 20.0;

    public function detectRevenueVolatility(string $countryId): array
    {
        $records = RevenueRecord::where('country_id', $countryId)
            ->orderBy('year', 'desc')
            ->limit(5)
            ->get();

        if ($records->count() < 2) {
            return [];
        }

        $signals = [];
        $sorted = $records->sortBy('year')->values();

        for ($i = 1; $i < $sorted->count(); $i++) {
            $prev = (float) $sorted[$i - 1]->total_revenue;
            $curr = (float) $sorted[$i]->total_revenue;

            if ($prev == 0) continue;

            $change = (($curr - $prev) / $prev) * 100;

            if ($change < -self::VOLATILITY_THRESHOLD) {
                $signal = FiscalRiskSignal::create([
                    'country_id' => $countryId,
                    'year' => $sorted[$i]->year,
                    'risk_type' => 'revenue_instability',
                    'severity' => abs($change) > 30 ? 'high' : 'moderate',
                    'description' => "Revenue declined by " . round(abs($change), 2) . "% from {$sorted[$i - 1]->year} to {$sorted[$i]->year}.",
                    'triggered_at' => now(),
                ]);
                $signals[] = $signal;

                Log::warning('Revenue volatility detected', [
                    'country_id' => $countryId,
                    'year' => $sorted[$i]->year,
                    'change_percent' => $change,
                ]);
            }
        }

        return $signals;
    }
}
