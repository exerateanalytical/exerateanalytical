<?php

namespace App\Services\Fiscal;

use App\Exceptions\DataIntegrityException;
use App\Models\FiscalRiskSignal;
use App\Models\NationalDebtRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DebtMonitorService
{
    public function getDebtRecord(string $countryId, int $year): ?NationalDebtRecord
    {
        return Cache::remember("fiscal:debt:{$countryId}:{$year}", 7200, function () use ($countryId, $year) {
            return NationalDebtRecord::where('country_id', $countryId)
                ->where('year', $year)
                ->latest()
                ->first();
        });
    }

    public function calculateDebtRisk(string $countryId, int $year): NationalDebtRecord
    {
        $record = NationalDebtRecord::where('country_id', $countryId)
            ->where('year', $year)
            ->latest()
            ->first();

        if (!$record) {
            throw new DataIntegrityException("No debt record found for country [{$countryId}] year [{$year}].");
        }

        $ratio = (float) $record->debt_to_gdp_ratio;
        $serviceRatio = (float) ($record->debt_service_ratio ?? 0);

        $classification = 'low';

        if ($ratio > 90) {
            $classification = 'critical';
        } elseif ($ratio > 70 && $serviceRatio > 30) {
            $classification = 'elevated';
        } elseif ($ratio > 60) {
            $classification = 'moderate';
        }

        DB::transaction(function () use ($record, $countryId, $year, $classification, $ratio) {
            $record->risk_classification = $classification;
            $record->save();

            if (in_array($classification, ['elevated', 'critical'])) {
                FiscalRiskSignal::create([
                    'country_id'  => $countryId,
                    'year'        => $year,
                    'risk_type'   => 'debt_sustainability',
                    'severity'    => $classification === 'critical' ? 'critical' : 'high',
                    'description' => "Debt-to-GDP ratio at {$ratio}% with classification: {$classification}.",
                    'triggered_at' => now(),
                ]);
            }
        });

        Cache::forget("fiscal:debt:{$countryId}:{$year}");

        Log::info('Debt risk calculated', [
            'country_id'     => $countryId,
            'year'           => $year,
            'classification' => $classification,
        ]);

        return $record;
    }
}
