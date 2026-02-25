<?php

namespace App\Services\Governance;

use App\Exceptions\DataIntegrityException;
use App\Exceptions\IndicatorWeightMismatchException;
use App\Models\GovernanceScore;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\Pillar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GovernanceIndexService
{
    private const WEIGHT_SUM_TOLERANCE = 0.01;

    /**
     * Normalize a single indicator value for a given country and year.
     */
    public function normalizeIndicator(string $indicatorId, string $countryId, int $year): void
    {
        $indicator = Indicator::findOrFail($indicatorId);

        $values = IndicatorValue::where('indicator_id', $indicatorId)
            ->where('country_id', $countryId)
            ->where('year', $year)
            ->get();

        if ($values->isEmpty()) {
            Log::warning('No values found for normalization', [
                'indicator_id' => $indicatorId,
                'country_id' => $countryId,
                'year' => $year,
            ]);
            return;
        }

        $rawValues = $values->pluck('raw_value')->map(fn ($v) => (float) $v);

        match ($indicator->normalization_method) {
            'minmax' => $this->applyMinMax($values, $rawValues, false),
            'inverse_minmax' => $this->applyMinMax($values, $rawValues, true),
            'zscore' => $this->applyZScore($values, $rawValues),
            default => throw new DataIntegrityException("Unknown normalization method: {$indicator->normalization_method}"),
        };
    }

    private function applyMinMax(Collection $values, Collection $rawValues, bool $inverse): void
    {
        $min = $rawValues->min();
        $max = $rawValues->max();
        $range = $max - $min;

        if ($range == 0) {
            throw new DataIntegrityException('Division by zero: min equals max in min-max normalization.');
        }

        foreach ($values as $value) {
            $raw = (float) $value->raw_value;
            $normalized = $inverse
                ? (1 - ($raw - $min) / $range) * 100
                : (($raw - $min) / $range) * 100;

            $value->normalized_value = round($normalized, 6);
            $value->save();
        }
    }

    private function applyZScore(Collection $values, Collection $rawValues): void
    {
        $count = $rawValues->count();
        if ($count < 2) {
            throw new DataIntegrityException('Insufficient data for z-score normalization: at least 2 values required.');
        }

        $mean = $rawValues->avg();
        $variance = $rawValues->reduce(fn ($carry, $v) => $carry + pow($v - $mean, 2), 0) / $count;
        $std = sqrt($variance);

        if ($std == 0) {
            throw new DataIntegrityException('Division by zero: standard deviation is zero in z-score normalization.');
        }

        foreach ($values as $value) {
            $normalized = ((float) $value->raw_value - $mean) / $std;
            $value->normalized_value = round($normalized, 6);
            $value->save();
        }
    }

    /**
     * Calculate the weighted score for a pillar.
     */
    public function calculatePillarScore(string $pillarId, string $countryId, int $year): float
    {
        $pillar = Pillar::findOrFail($pillarId);
        $indicators = Indicator::where('pillar_id', $pillarId)
            ->where('country_id', $countryId)
            ->where('version', $pillar->version)
            ->active()
            ->get();

        if ($indicators->isEmpty()) {
            Log::warning('No active indicators for pillar', ['pillar_id' => $pillarId]);
            return 0.0;
        }

        $weightSum = $indicators->sum(fn ($i) => (float) $i->weight);
        if (abs($weightSum - 100.0) > self::WEIGHT_SUM_TOLERANCE) {
            throw new IndicatorWeightMismatchException(
                "Indicator weights for pillar [{$pillarId}] sum to {$weightSum}, expected 100."
            );
        }

        $weightedScore = 0.0;
        foreach ($indicators as $indicator) {
            $value = IndicatorValue::where('indicator_id', $indicator->id)
                ->where('country_id', $countryId)
                ->where('year', $year)
                ->whereNotNull('normalized_value')
                ->latest()
                ->first();

            if (!$value) {
                throw new DataIntegrityException(
                    "Missing normalized value for indicator [{$indicator->id}] in year [{$year}]."
                );
            }

            $weightedScore += ((float) $value->normalized_value * (float) $indicator->weight) / 100;
        }

        return round($weightedScore, 6);
    }

    /**
     * Calculate and persist the composite governance score.
     */
    public function calculateCompositeScore(string $countryId, ?string $regionId, int $year): GovernanceScore
    {
        $pillars = Pillar::where('country_id', $countryId)
            ->active()
            ->orderBy('version', 'desc')
            ->get();

        if ($pillars->isEmpty()) {
            throw new DataIntegrityException("No active pillars found for country [{$countryId}].");
        }

        $latestVersion = $pillars->first()->version;
        $activePillars = $pillars->where('version', $latestVersion);

        $weightSum = $activePillars->sum(fn ($p) => (float) $p->weight);
        if (abs($weightSum - 100.0) > self::WEIGHT_SUM_TOLERANCE) {
            throw new IndicatorWeightMismatchException(
                "Pillar weights for country [{$countryId}] sum to {$weightSum}, expected 100."
            );
        }

        $pillarScores = [];
        $compositeScore = 0.0;

        foreach ($activePillars as $pillar) {
            $score = $this->calculatePillarScore($pillar->id, $countryId, $year);
            $pillarScores[$pillar->id] = [
                'name' => $pillar->name,
                'score' => $score,
                'weight' => (float) $pillar->weight,
            ];
            $compositeScore += $score * (float) $pillar->weight / 100;
        }

        $scoreRecord = GovernanceScore::updateOrCreate(
            [
                'country_id' => $countryId,
                'region_id' => $regionId,
                'year' => $year,
                'version' => $latestVersion,
            ],
            [
                'pillar_scores' => $pillarScores,
                'composite_score' => round($compositeScore, 2),
                'calculated_at' => now(),
                'calculated_by' => auth()->id(),
            ]
        );

        Cache::forget("governance:{$countryId}:{$year}");

        return $scoreRecord;
    }

    /**
     * Run sensitivity simulation by varying each pillar weight by +/- 5%.
     */
    public function runSensitivitySimulation(string $countryId, int $year): array
    {
        $pillars = Pillar::where('country_id', $countryId)->active()->get();
        $baseline = $this->getCompositeScore($countryId, $year);

        if ($baseline === null) {
            throw new DataIntegrityException(
                "No baseline composite score found for country [{$countryId}] in year [{$year}]. Run calculateCompositeScore() first."
            );
        }

        $results = [];

        foreach ($pillars as $pillar) {
            foreach ([5, -5] as $delta) {
                $simPillars = $pillars->map(function ($p) use ($pillar, $delta) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'weight' => $p->id === $pillar->id
                            ? max(0, min(100, (float) $p->weight + $delta))
                            : (float) $p->weight,
                    ];
                });

                $totalWeight = $simPillars->sum('weight');
                if ($totalWeight == 0) continue;

                $simScore = 0.0;
                foreach ($simPillars as $sim) {
                    try {
                        $pillarScore = $this->calculatePillarScore($sim['id'], $countryId, $year);
                        $simScore += $pillarScore * ($sim['weight'] / $totalWeight);
                    } catch (\Throwable $e) {
                        Log::warning('Sensitivity simulation pillar score failed', ['error' => $e->getMessage()]);
                    }
                }

                $results[] = [
                    'pillar_id' => $pillar->id,
                    'pillar_name' => $pillar->name,
                    'delta' => $delta,
                    'simulated_score' => round($simScore, 4),
                    'impact_variance' => round($simScore - $baseline, 4),
                ];
            }
        }

        return $results;
    }

    public function getCompositeScore(string $countryId, int $year): ?float
    {
        $score = GovernanceScore::where('country_id', $countryId)
            ->where('year', $year)
            ->whereNull('region_id')
            ->latest('calculated_at')
            ->value('composite_score');

        return $score !== null ? (float) $score : null;
    }

    public function getTrend(string $countryId, int $years = 5): Collection
    {
        return GovernanceScore::where('country_id', $countryId)
            ->whereNull('region_id')
            ->orderBy('year', 'desc')
            ->limit($years)
            ->get(['year', 'composite_score', 'pillar_scores', 'version', 'calculated_at']);
    }
}
