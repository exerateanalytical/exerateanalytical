<?php

namespace App\Services\Development;

use App\Models\RegionalEquityMetric;
use App\Models\RiskSignal;
use App\Models\ServiceAccessRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RegionalEquityService
{
    private const EQUITY_DETERIORATION_THRESHOLD = 15.0;

    public function calculateVariance(string $countryId, int $year, string $metric): float
    {
        $values = ServiceAccessRecord::where('country_id', $countryId)
            ->where('year', $year)
            ->whereNotNull('region_id')
            ->whereNotNull($metric)
            ->pluck($metric)
            ->map(fn ($v) => (float) $v);

        if ($values->count() < 2) {
            return 0.0;
        }

        $mean = $values->avg();
        $variance = $values->reduce(fn ($carry, $v) => $carry + pow($v - $mean, 2), 0.0) / $values->count();

        return round($variance, 4);
    }

    public function calculateEquityScore(string $countryId, int $year): RegionalEquityMetric
    {
        $metrics = [
            'electricity_variance' => 'electricity_access_percent',
            'water_variance' => 'safe_water_access_percent',
            'road_density_variance' => 'road_density_km_per_100km2',
            'healthcare_density_variance' => 'healthcare_facilities_per_10000',
            'education_density_variance' => 'schools_per_10000',
            'digital_access_variance' => 'internet_penetration_percent',
        ];

        $variances = [];
        foreach ($metrics as $varianceKey => $column) {
            $variances[$varianceKey] = $this->calculateVariance($countryId, $year, $column);
        }

        // Compute equity score: lower variance = higher equity
        // Normalize variances and invert
        $maxVariance = max(array_values($variances)) ?: 1;
        $normalizedVariances = array_map(fn ($v) => $v / $maxVariance, $variances);
        $avgNormalizedVariance = array_sum($normalizedVariances) / count($normalizedVariances);
        $equityScore = round((1 - $avgNormalizedVariance) * 100, 2);

        $metric = RegionalEquityMetric::updateOrCreate(
            ['country_id' => $countryId, 'year' => $year],
            array_merge($variances, [
                'equity_score' => $equityScore,
                'calculated_at' => now(),
            ])
        );

        // Check for deterioration
        $previousMetric = RegionalEquityMetric::where('country_id', $countryId)
            ->where('year', $year - 1)
            ->first();

        if ($previousMetric && $previousMetric->equity_score) {
            $change = ((float) $equityScore - (float) $previousMetric->equity_score) / (float) $previousMetric->equity_score * 100;
            if ($change < -self::EQUITY_DETERIORATION_THRESHOLD) {
                RiskSignal::create([
                    'country_id' => $countryId,
                    'signal_type' => 'regional_equity_deterioration',
                    'severity' => 'high',
                    'module' => 'ServiceAccess',
                    'description' => "Equity score deteriorated by " . round(abs($change), 2) . "% YoY.",
                    'triggered_at' => now(),
                ]);
            }
        }

        return $metric;
    }
}
