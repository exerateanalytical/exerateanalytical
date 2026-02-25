<?php

namespace App\Services\Risk;

use App\Models\FiscalRiskSignal;
use App\Models\Region;
use App\Models\RiskSignal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RiskIntelligenceService
{
    protected const SEVERITY_MAP = [
        'low'      => 25,
        'moderate' => 50,
        'high'     => 75,
        'critical' => 100,
    ];

    protected const DOMAIN_WEIGHTS = [
        'governance'     => 0.4,
        'fiscal'         => 0.35,
        'accountability' => 0.25,
    ];

    public function getDomainAverages(string $countryId): array
    {
        $windowStart = Carbon::now()->subMonths(12);
        $domains     = $this->collectDomains($countryId, $windowStart);

        return [
            'governance'     => round($this->domainAvg($domains['governance']), 2),
            'fiscal'         => round($this->domainAvg($domains['fiscal']), 2),
            'accountability' => round($this->domainAvg($domains['accountability']), 2),
            'weights'        => self::DOMAIN_WEIGHTS,
        ];
    }

    public function getMonthlyHistory(string $countryId): array
    {
        $windowStart = Carbon::now()->subMonths(12);
        $domains     = $this->collectDomains($countryId, $windowStart);

        return $this->computeMonthlyScores($domains);
    }

    protected function computeMonthlyScores(array $domains): array
    {
        $months = [];

        for ($i = 11; $i >= 0; $i--) {
            $from   = Carbon::now()->startOfMonth()->subMonths($i);
            $before = $from->copy()->addMonth();
            $sliced = $this->filterDomains($domains, $from, $before);

            $months[] = [
                'month'          => $from->format('Y-m'),
                'weighted_score' => round($this->weightedScore($sliced), 2),
            ];
        }

        return $months;
    }

    public function getNationalRiskSummary(string $countryId): array
    {
        return Cache::remember(
            "risk:intelligence:{$countryId}",
            900,
            fn () => $this->computeSummary($countryId)
        );
    }

    public function simulateRegionalShock(
        string $countryId,
        string $regionId,
        float $shockPercent
    ): array {
        // Step a: baseline national summary (served from cache; no extra DB hit)
        $national             = $this->getNationalRiskSummary($countryId);
        $baselineNationalRisk = $national['national_risk_score'];

        // Steps b/c: collect regional domains and build monthly history
        $windowStart    = Carbon::now()->subMonths(12);
        $domains        = $this->collectRegionalDomainsForConcentration($countryId, $regionId, $windowStart);
        $monthlyHistory = $this->computeMonthlyScores($domains);

        // Baseline regional score and fragility
        $baselineScore      = round($this->weightedScore($domains), 2);
        $baselineVolatility = $this->computeVolatilityMetrics($monthlyHistory);
        $baselineFragility  = $this->computeFragilityMetrics(
            $baselineScore,
            $baselineVolatility['volatility_index'],
            $baselineVolatility['acceleration']
        )['fragility_index'];

        // Step d: apply shock to last 3 months of monthly history
        $shockedHistory = $monthlyHistory;
        $n              = count($shockedHistory);

        for ($i = max(0, $n - 3); $i < $n; $i++) {
            $shockedHistory[$i]['weighted_score'] = round(
                min(100.0, $shockedHistory[$i]['weighted_score'] * (1 + $shockPercent / 100)),
                2
            );
        }

        // Step e: recompute volatility and fragility from shocked history
        $shockedScores      = array_column($shockedHistory, 'weighted_score');
        $postShockScore     = count($shockedScores) >= 3
            ? round(array_sum(array_slice($shockedScores, -3)) / 3, 2)
            : $baselineScore;
        $shockedVolatility  = $this->computeVolatilityMetrics($shockedHistory);
        $postShockFragility = $this->computeFragilityMetrics(
            $postShockScore,
            $shockedVolatility['volatility_index'],
            $shockedVolatility['acceleration']
        )['fragility_index'];

        // Step f: rebuild concentration pool with shocked region replaced
        $baselinePool  = [];
        $postShockPool = [];

        foreach (Region::where('country_id', $countryId)->get() as $region) {
            $rDomains    = $this->collectRegionalDomainsForConcentration($countryId, $region->id, $windowStart);
            $rScore      = round($this->weightedScore($rDomains), 2);
            $rVolMetrics = $this->computeVolatilityMetrics($this->computeMonthlyScores($rDomains));
            $rFragility  = $this->computeFragilityMetrics(
                $rScore,
                $rVolMetrics['volatility_index'],
                $rVolMetrics['acceleration']
            )['fragility_index'];

            $baselinePool[]  = $rFragility;
            $postShockPool[] = ($region->id === $regionId) ? $postShockFragility : $rFragility;
        }

        $baselineGini       = $this->calculateGini($baselinePool);
        $postShockGini      = $this->calculateGini($postShockPool);
        $concentrationDelta = round($postShockGini - $baselineGini, 4);

        // Shift the national risk by the mean change across the fragility pool
        $poolCount             = count($baselinePool);
        $poolMeanShift         = $poolCount > 0
            ? (array_sum($postShockPool) - array_sum($baselinePool)) / $poolCount
            : 0.0;
        $postShockNationalRisk = round(min(100.0, max(0.0, $baselineNationalRisk + $poolMeanShift)), 2);

        $fragilityDelta  = round($postShockFragility - $baselineFragility, 2);
        $riskDelta       = round($postShockNationalRisk - $baselineNationalRisk, 2);
        // Divergence delta: change in (regional_fragility − national_risk) after shock
        $divergenceDelta = round($fragilityDelta - $riskDelta, 2);

        return [
            'baseline_national_risk'   => $baselineNationalRisk,
            'post_shock_national_risk' => $postShockNationalRisk,
            'risk_delta'               => $riskDelta,
            'baseline_fragility'       => $baselineFragility,
            'post_shock_fragility'     => $postShockFragility,
            'fragility_delta'          => $fragilityDelta,
            'concentration_delta'      => $concentrationDelta,
            'divergence_delta'         => $divergenceDelta,
        ];
    }

    public function simulateCascadingShock(
        string $countryId,
        array $regionShocks,
        float $contagionFactor
    ): array {
        // Step a: clamp contagionFactor to [0, 1]
        $contagionFactor = min(1.0, max(0.0, $contagionFactor));

        // Step b: baseline national summary (served from cache)
        $national             = $this->getNationalRiskSummary($countryId);
        $baselineNationalRisk = $national['national_risk_score'];

        // Secondary shock percent = avg(primary shocks) × contagionFactor
        $primaryPercents       = array_values($regionShocks);
        $avgPrimaryShock       = count($primaryPercents) > 0
            ? array_sum($primaryPercents) / count($primaryPercents)
            : 0.0;
        $secondaryShockPercent = $avgPrimaryShock * $contagionFactor;

        $windowStart   = Carbon::now()->subMonths(12);
        $baselinePool  = [];
        $postShockPool = [];

        // Step c/d: process every region
        foreach (Region::where('country_id', $countryId)->get() as $region) {
            $rDomains       = $this->collectRegionalDomainsForConcentration($countryId, $region->id, $windowStart);
            $rScore         = round($this->weightedScore($rDomains), 2);
            $monthlyHistory = $this->computeMonthlyScores($rDomains);
            $rVolMetrics    = $this->computeVolatilityMetrics($monthlyHistory);
            $rFragility     = $this->computeFragilityMetrics(
                $rScore,
                $rVolMetrics['volatility_index'],
                $rVolMetrics['acceleration']
            )['fragility_index'];

            $baselinePool[] = $rFragility;

            // Apply primary or secondary shock to monthly history
            $shockedHistory = $monthlyHistory;
            $n              = count($shockedHistory);

            if (array_key_exists($region->id, $regionShocks)) {
                // Primary shock: last 3 months
                $shockPercent = $regionShocks[$region->id];
                for ($i = max(0, $n - 3); $i < $n; $i++) {
                    $shockedHistory[$i]['weighted_score'] = round(
                        min(100.0, $shockedHistory[$i]['weighted_score'] * (1 + $shockPercent / 100)),
                        2
                    );
                }
            } elseif ($n > 0) {
                // Secondary shock (contagion): last month only
                $shockedHistory[$n - 1]['weighted_score'] = round(
                    min(100.0, $shockedHistory[$n - 1]['weighted_score'] * (1 + $secondaryShockPercent / 100)),
                    2
                );
            }

            $shockedScores  = array_column($shockedHistory, 'weighted_score');
            $postShockScore = count($shockedScores) >= 3
                ? round(array_sum(array_slice($shockedScores, -3)) / 3, 2)
                : $rScore;
            $shockedVol     = $this->computeVolatilityMetrics($shockedHistory);
            $postFragility  = $this->computeFragilityMetrics(
                $postShockScore,
                $shockedVol['volatility_index'],
                $shockedVol['acceleration']
            )['fragility_index'];

            $postShockPool[] = $postFragility;
        }

        // Step e: aggregate metrics
        $baselineGini       = $this->calculateGini($baselinePool);
        $postShockGini      = $this->calculateGini($postShockPool);
        $concentrationDelta = round($postShockGini - $baselineGini, 4);

        $poolCount             = count($baselinePool);
        $poolMeanShift         = $poolCount > 0
            ? (array_sum($postShockPool) - array_sum($baselinePool)) / $poolCount
            : 0.0;
        $postShockNationalRisk = round(min(100.0, max(0.0, $baselineNationalRisk + $poolMeanShift)), 2);
        $riskDelta             = round($postShockNationalRisk - $baselineNationalRisk, 2);

        // Systemic instability index: population stddev of post-shock fragility pool
        $systemicInstabilityIndex = 0.0;
        if ($poolCount > 0) {
            $mean     = array_sum($postShockPool) / $poolCount;
            $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $postShockPool)) / $poolCount;
            $systemicInstabilityIndex = round(sqrt($variance), 2);
        }

        return [
            'baseline_national_risk'     => $baselineNationalRisk,
            'post_shock_national_risk'   => $postShockNationalRisk,
            'risk_delta'                 => $riskDelta,
            'concentration_delta'        => $concentrationDelta,
            'systemic_instability_index' => $systemicInstabilityIndex,
        ];
    }

    public function simulateCrossCountryShock(
        string $originCountryId,
        array $countryExposureMatrix,
        float $originShockPercent,
        float $globalContagionFactor
    ): array {
        // Step a: clamp globalContagionFactor to [0, 1]
        $globalContagionFactor = min(1.0, max(0.0, $globalContagionFactor));

        // Step b: origin baseline (from cache)
        $originBaseline     = $this->getNationalRiskSummary($originCountryId);
        $originBaselineRisk = $originBaseline['national_risk_score'];

        // Step c: origin post-shock — all regions shocked equally at originShockPercent
        $originShock     = $this->computeNationalShock($originCountryId, $originShockPercent);
        $originPostShock = $originShock['post_shock_risk'];
        $originDelta     = $originShock['risk_delta'];

        // Step d: propagate shock to each exposed country
        $countryImpacts    = [];
        $allPostShockRisks = [$originPostShock];
        $allRiskDeltas     = [$originDelta];

        foreach ($countryExposureMatrix as $countryId => $exposureWeight) {
            $exposureWeight         = min(1.0, max(0.0, (float) $exposureWeight));
            $propagatedShockPercent = $originShockPercent * $exposureWeight * $globalContagionFactor;

            $countryShock = $this->computeNationalShock($countryId, $propagatedShockPercent);

            $countryImpacts[$countryId] = [
                'baseline_risk'            => $countryShock['baseline_risk'],
                'post_shock_risk'          => $countryShock['post_shock_risk'],
                'risk_delta'               => $countryShock['risk_delta'],
                'propagated_shock_percent' => round($propagatedShockPercent, 2),
            ];

            $allPostShockRisks[] = $countryShock['post_shock_risk'];
            $allRiskDeltas[]     = $countryShock['risk_delta'];
        }

        // Step e: global aggregate metrics
        $totalCountries          = count($allPostShockRisks);
        $globalSystemicRiskDelta = $totalCountries > 0
            ? round(array_sum($allRiskDeltas) / $totalCountries, 2)
            : 0.0;

        $globalInstabilityIndex = 0.0;
        if ($totalCountries > 0) {
            $mean     = array_sum($allPostShockRisks) / $totalCountries;
            $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $allPostShockRisks)) / $totalCountries;
            $globalInstabilityIndex = round(sqrt($variance), 2);
        }

        return [
            'origin_baseline_risk'       => $originBaselineRisk,
            'origin_post_shock_risk'     => $originPostShock,
            'origin_risk_delta'          => $originDelta,
            'country_impacts'            => $countryImpacts,
            'global_systemic_risk_delta' => $globalSystemicRiskDelta,
            'global_instability_index'   => $globalInstabilityIndex,
        ];
    }

    public function computeSystemicImportance(array $exposureMatrix): array
    {
        $results = [];

        foreach ($exposureMatrix as $countryId => $outgoingEdges) {
            $summary = $this->getNationalRiskSummary((string) $countryId);

            $outgoingExposureWeight = array_sum(array_values($outgoingEdges));

            $stressAmplificationScore = round(
                ($summary['fragility_index']     * 0.4)
                + ($summary['volatility_index']  * 0.3)
                + ($summary['national_risk_score'] * 0.3),
                2
            );

            $systemicImportanceScore = $outgoingExposureWeight > 0
                ? round($stressAmplificationScore * log(1 + $outgoingExposureWeight), 4)
                : 0.0;

            $results[] = [
                'country_id'                  => $countryId,
                'baseline_risk'               => $summary['national_risk_score'],
                'fragility_index'             => $summary['fragility_index'],
                'volatility_index'            => $summary['volatility_index'],
                'outgoing_exposure_weight'    => $outgoingExposureWeight,
                'stress_amplification_score'  => $stressAmplificationScore,
                'systemic_importance_score'   => $systemicImportanceScore,
            ];
        }

        usort($results, fn ($a, $b) => $b['systemic_importance_score'] <=> $a['systemic_importance_score']);

        return $results;
    }

    private function computeNationalShock(string $countryId, float $shockPercent): array
    {
        $national         = $this->getNationalRiskSummary($countryId);
        $baselineNational = $national['national_risk_score'];

        $windowStart   = Carbon::now()->subMonths(12);
        $baselinePool  = [];
        $postShockPool = [];

        foreach (Region::where('country_id', $countryId)->get() as $region) {
            $rDomains       = $this->collectRegionalDomainsForConcentration($countryId, $region->id, $windowStart);
            $rScore         = round($this->weightedScore($rDomains), 2);
            $monthlyHistory = $this->computeMonthlyScores($rDomains);
            $rVolMetrics    = $this->computeVolatilityMetrics($monthlyHistory);
            $rFragility     = $this->computeFragilityMetrics(
                $rScore,
                $rVolMetrics['volatility_index'],
                $rVolMetrics['acceleration']
            )['fragility_index'];

            $baselinePool[] = $rFragility;

            // Shock all regions equally: last 3 months
            $shockedHistory = $monthlyHistory;
            $n              = count($shockedHistory);

            for ($i = max(0, $n - 3); $i < $n; $i++) {
                $shockedHistory[$i]['weighted_score'] = round(
                    min(100.0, $shockedHistory[$i]['weighted_score'] * (1 + $shockPercent / 100)),
                    2
                );
            }

            $shockedScores  = array_column($shockedHistory, 'weighted_score');
            $postShockScore = count($shockedScores) >= 3
                ? round(array_sum(array_slice($shockedScores, -3)) / 3, 2)
                : $rScore;
            $shockedVol     = $this->computeVolatilityMetrics($shockedHistory);
            $postFragility  = $this->computeFragilityMetrics(
                $postShockScore,
                $shockedVol['volatility_index'],
                $shockedVol['acceleration']
            )['fragility_index'];

            $postShockPool[] = $postFragility;
        }

        $poolCount    = count($baselinePool);
        $poolMeanShift = $poolCount > 0
            ? (array_sum($postShockPool) - array_sum($baselinePool)) / $poolCount
            : 0.0;
        $postShockNational = round(min(100.0, max(0.0, $baselineNational + $poolMeanShift)), 2);

        return [
            'baseline_risk'  => $baselineNational,
            'post_shock_risk' => $postShockNational,
            'risk_delta'     => round($postShockNational - $baselineNational, 2),
        ];
    }

    private function computeSummary(string $countryId): array
    {
        $windowStart = Carbon::now()->subMonths(12);
        $domains     = $this->collectDomains($countryId, $windowStart);

        $allSignals = $domains['governance']
            ->concat($domains['fiscal'])
            ->concat($domains['accountability']);

        if ($allSignals->isEmpty()) {
            return [
                'national_risk_score' => 0.0,
                'risk_level'          => 'low',
                'trend'               => 'stable',
                'active_categories'   => [],
                'signal_count'        => 0,
                'last_updated'        => null,
                'confidence'          => 'low',
                'executive_summary'   => 'National risk posture is LOW and stable. Insufficient signal data for domain attribution.',
                'volatility_index'    => 0.0,
                'acceleration'        => 0.0,
                'stability_label'     => 'stable',
                'fragility_index'          => 0.0,
                'fragility_label'          => 'resilient',
                'risk_concentration_index' => 0.0,
                'concentration_label'      => 'evenly_distributed',
                'top_20_percent_share'     => 0.0,
                'projected_risk_3m'        => 0.0,
                'projection_confidence'    => 'low',
                'projection_trend'         => 'stable',
                'projected_risk_lower_3m'  => 0.0,
                'projected_risk_upper_3m'  => 0.0,
                'regime_shift_detected'    => false,
                'regime_shift_type'        => null,
                'regime_shift_severity'    => 0.0,
            ];
        }

        $nationalRiskScore  = round($this->weightedScore($domains), 2);
        $riskLevel          = $this->deriveRiskLevel($nationalRiskScore);
        $trend              = $this->computeTrend($domains);
        $activeCategories   = collect(['accountability', 'fiscal', 'governance'])
            ->filter(fn ($domain) => $domains[$domain]->isNotEmpty())
            ->values()
            ->all();
        $signalCount        = $allSignals->count();
        $monthlyHistory       = $this->computeMonthlyScores($domains);
        $volatilityMetrics    = $this->computeVolatilityMetrics($monthlyHistory);
        $fragilityMetrics     = $this->computeFragilityMetrics(
            $nationalRiskScore,
            $volatilityMetrics['volatility_index'],
            $volatilityMetrics['acceleration']
        );
        $concentrationMetrics = $this->computeConcentrationMetrics($countryId);
        $projectedRisk        = $this->calculateProjection($monthlyHistory, $volatilityMetrics['acceleration']);
        $band                 = $this->computeProjectionBand($projectedRisk, $volatilityMetrics['volatility_index']);
        $shift                = $this->computeRegimeShift($monthlyHistory, $volatilityMetrics['volatility_index']);

        return [
            'national_risk_score'      => $nationalRiskScore,
            'risk_level'               => $riskLevel,
            'trend'                    => $trend,
            'active_categories'        => $activeCategories,
            'signal_count'             => $signalCount,
            'last_updated'             => $allSignals
                ->pluck('triggered_at')
                ->filter()
                ->max()
                ?->toIso8601String(),
            'confidence'               => $signalCount >= 3 ? 'normal' : 'low',
            'executive_summary'        => $this->buildExecutiveSummary($riskLevel, $trend, $activeCategories),
            'volatility_index'         => $volatilityMetrics['volatility_index'],
            'acceleration'             => $volatilityMetrics['acceleration'],
            'stability_label'          => $volatilityMetrics['stability_label'],
            'fragility_index'          => $fragilityMetrics['fragility_index'],
            'fragility_label'          => $fragilityMetrics['fragility_label'],
            'risk_concentration_index' => $concentrationMetrics['risk_concentration_index'],
            'concentration_label'      => $concentrationMetrics['concentration_label'],
            'top_20_percent_share'     => $concentrationMetrics['top_20_percent_share'],
            'projected_risk_3m'        => $projectedRisk,
            'projection_confidence'    => $this->deriveProjectionConfidence($volatilityMetrics['volatility_index']),
            'projection_trend'         => $this->deriveProjectionTrend($nationalRiskScore, $projectedRisk),
            'projected_risk_lower_3m'  => $band['lower'],
            'projected_risk_upper_3m'  => $band['upper'],
            'regime_shift_detected'    => $shift['regime_shift_detected'],
            'regime_shift_type'        => $shift['regime_shift_type'],
            'regime_shift_severity'    => $shift['regime_shift_severity'],
        ];
    }

    protected function computeConcentrationMetrics(string $countryId): array
    {
        $windowStart     = Carbon::now()->subMonths(12);
        $fragilityValues = Region::where('country_id', $countryId)
            ->get()
            ->map(function ($region) use ($countryId, $windowStart) {
                $domains           = $this->collectRegionalDomainsForConcentration($countryId, $region->id, $windowStart);
                $score             = round($this->weightedScore($domains), 2);
                $volatilityMetrics = $this->computeVolatilityMetrics($this->computeMonthlyScores($domains));
                $fragilityMetrics  = $this->computeFragilityMetrics(
                    $score,
                    $volatilityMetrics['volatility_index'],
                    $volatilityMetrics['acceleration']
                );

                return $fragilityMetrics['fragility_index'];
            })
            ->all();

        $gini  = $this->calculateGini($fragilityValues);
        $top20 = $this->calculateTop20Share($fragilityValues);

        return [
            'risk_concentration_index' => $gini,
            'concentration_label'      => $this->deriveConcentrationLabel($gini),
            'top_20_percent_share'     => $top20,
        ];
    }

    private function collectRegionalDomainsForConcentration(string $countryId, string $regionId, Carbon $windowStart): array
    {
        $governance = RiskSignal::where('country_id', $countryId)
            ->where(fn ($q) => $q->where('module', 'Governance')->orWhereNull('module'))
            ->where('metadata->region_id', $regionId)
            ->where('triggered_at', '>=', $windowStart)
            ->get()
            ->map(fn ($s) => ['severity' => $s->severity, 'triggered_at' => $s->triggered_at]);

        $accountability = RiskSignal::where('country_id', $countryId)
            ->where('module', 'Accountability')
            ->where('metadata->region_id', $regionId)
            ->where('triggered_at', '>=', $windowStart)
            ->get()
            ->map(fn ($s) => ['severity' => $s->severity, 'triggered_at' => $s->triggered_at]);

        return [
            'governance'     => $governance,
            'fiscal'         => collect(),
            'accountability' => $accountability,
        ];
    }

    private function calculateGini(array $values): float
    {
        $n = count($values);

        if ($n < 2) {
            return 0.0;
        }

        sort($values);

        $sum = array_sum($values);

        if ($sum == 0) {
            return 0.0;
        }

        $numerator = 0.0;
        foreach ($values as $i => $v) {
            $numerator += ($i + 1) * $v;
        }

        return round((2 * $numerator) / ($n * $sum) - ($n + 1) / $n, 4);
    }

    private function calculateTop20Share(array $values): float
    {
        $total = array_sum($values);

        if ($total == 0) {
            return 0.0;
        }

        rsort($values);
        $topCount = (int) ceil(0.2 * count($values));
        $topSum   = array_sum(array_slice($values, 0, $topCount));

        return round($topSum / $total, 4);
    }

    private function deriveConcentrationLabel(float $gini): string
    {
        return match (true) {
            $gini <= 0.2 => 'evenly_distributed',
            $gini <= 0.4 => 'moderately_concentrated',
            $gini <= 0.6 => 'concentrated',
            default      => 'highly_concentrated',
        };
    }

    protected function calculateProjection(array $monthlyHistory, float $acceleration): float
    {
        $scores = array_column($monthlyHistory, 'weighted_score');
        $last6  = array_slice($scores, -6);
        $n      = count($last6);

        if ($n < 2) {
            return 0.0;
        }

        $xMean = ($n - 1) / 2.0;
        $yMean = array_sum($last6) / $n;

        $numerator   = 0.0;
        $denominator = 0.0;

        foreach ($last6 as $i => $y) {
            $numerator   += ($i - $xMean) * ($y - $yMean);
            $denominator += ($i - $xMean) ** 2;
        }

        $slope     = $denominator != 0 ? $numerator / $denominator : 0.0;
        $lastScore = end($last6);
        $projected = $lastScore + ($slope * 3);

        if ($acceleration > 0) {
            $projected += $acceleration * 0.5;
        }

        return round(min(100.0, max(0.0, $projected)), 2);
    }

    protected function deriveProjectionConfidence(float $volatility): string
    {
        return match (true) {
            $volatility <= 5  => 'high',
            $volatility <= 15 => 'moderate',
            default           => 'low',
        };
    }

    protected function deriveProjectionTrend(float $currentScore, float $projectedScore): string
    {
        $delta = $projectedScore - $currentScore;

        return match (true) {
            $delta <= -5 => 'improving',
            $delta <= 5  => 'stable',
            default      => 'deteriorating',
        };
    }

    protected function computeProjectionBand(float $projected, float $volatility): array
    {
        $bandWidth = $volatility * 0.5;

        return [
            'lower' => round(min(100.0, max(0.0, $projected - $bandWidth)), 2),
            'upper' => round(min(100.0, max(0.0, $projected + $bandWidth)), 2),
        ];
    }

    protected function computeRegimeShift(array $monthlyHistory, float $volatility): array
    {
        $delta = $this->calculateRegimeDelta($monthlyHistory);

        if (abs($delta) >= 12 && $volatility >= 8) {
            return [
                'regime_shift_detected'  => true,
                'regime_shift_type'      => $delta > 0 ? 'escalation' : 'stabilization',
                'regime_shift_severity'  => round(abs($delta), 2),
            ];
        }

        return [
            'regime_shift_detected'  => false,
            'regime_shift_type'      => null,
            'regime_shift_severity'  => 0.0,
        ];
    }

    private function calculateRegimeDelta(array $monthlyHistory): float
    {
        $scores = array_column($monthlyHistory, 'weighted_score');

        if (count($scores) < 6) {
            return 0.0;
        }

        $recent   = array_slice($scores, -3);
        $previous = array_slice($scores, -6, 3);

        return array_sum($recent) / 3 - array_sum($previous) / 3;
    }

    private function collectDomains(string $countryId, Carbon $windowStart): array
    {
        $governance = RiskSignal::where('country_id', $countryId)
            ->where(fn ($q) => $q->where('module', 'Governance')->orWhereNull('module'))
            ->where('triggered_at', '>=', $windowStart)
            ->get()
            ->map(fn ($s) => ['severity' => $s->severity, 'triggered_at' => $s->triggered_at]);

        $accountability = RiskSignal::where('country_id', $countryId)
            ->where('module', 'Accountability')
            ->where('triggered_at', '>=', $windowStart)
            ->get()
            ->map(fn ($s) => ['severity' => $s->severity, 'triggered_at' => $s->triggered_at]);

        $fiscal = FiscalRiskSignal::where('country_id', $countryId)
            ->where('triggered_at', '>=', $windowStart)
            ->get()
            ->map(fn ($s) => ['severity' => $s->severity, 'triggered_at' => $s->triggered_at]);

        return [
            'governance'     => $governance,
            'fiscal'         => $fiscal,
            'accountability' => $accountability,
        ];
    }

    protected function weightedScore(array $domains): float
    {
        return ($this->domainAvg($domains['governance'])     * self::DOMAIN_WEIGHTS['governance'])
            + ($this->domainAvg($domains['fiscal'])          * self::DOMAIN_WEIGHTS['fiscal'])
            + ($this->domainAvg($domains['accountability'])  * self::DOMAIN_WEIGHTS['accountability']);
    }

    protected function filterDomains(array $domains, Carbon $from, ?Carbon $before = null): array
    {
        $slice = fn (Collection $signals) => $signals->filter(
            fn ($s) => $s['triggered_at']
                && $s['triggered_at']->gte($from)
                && ($before === null || $s['triggered_at']->lt($before))
        );

        return [
            'governance'     => $slice($domains['governance']),
            'fiscal'         => $slice($domains['fiscal']),
            'accountability' => $slice($domains['accountability']),
        ];
    }

    protected function domainAvg(Collection $signals): float
    {
        if ($signals->isEmpty()) {
            return 0.0;
        }

        return $signals->avg(fn ($s) => $this->normalizeSeverity($s['severity']));
    }

    protected function normalizeSeverity(string $severity): int
    {
        return self::SEVERITY_MAP[strtolower($severity)] ?? 50;
    }

    protected function deriveRiskLevel(float $score): string
    {
        return match (true) {
            $score <= 25 => 'low',
            $score <= 50 => 'moderate',
            $score <= 75 => 'high',
            default      => 'critical',
        };
    }

    protected function computeTrend(array $domains): string
    {
        $now           = Carbon::now();
        $recentCutoff  = $now->copy()->subDays(30);
        $baselineStart = $now->copy()->subDays(90);

        $recentDomains   = $this->filterDomains($domains, $recentCutoff);
        $baselineDomains = $this->filterDomains($domains, $baselineStart, $recentCutoff);

        $baselineEmpty = $baselineDomains['governance']->isEmpty()
            && $baselineDomains['fiscal']->isEmpty()
            && $baselineDomains['accountability']->isEmpty();

        if ($baselineEmpty) {
            return 'stable';
        }

        $recentWeightedScore   = $this->weightedScore($recentDomains);
        $baselineWeightedScore = $this->weightedScore($baselineDomains);

        if ($recentWeightedScore > $baselineWeightedScore + 5) {
            return 'deteriorating';
        }

        if ($recentWeightedScore < $baselineWeightedScore - 5) {
            return 'improving';
        }

        return 'stable';
    }

    protected function computeVolatilityMetrics(array $monthlyHistory): array
    {
        $volatility = $this->calculateVolatility($monthlyHistory);

        return [
            'volatility_index' => $volatility,
            'acceleration'     => $this->calculateAcceleration($monthlyHistory),
            'stability_label'  => $this->deriveStabilityLabel($volatility),
        ];
    }

    private function calculateVolatility(array $monthlyHistory): float
    {
        $scores = array_column($monthlyHistory, 'weighted_score');
        $n      = count($scores);

        if ($n === 0) {
            return 0.0;
        }

        $mean     = array_sum($scores) / $n;
        $variance = array_sum(array_map(fn ($s) => ($s - $mean) ** 2, $scores)) / $n;

        return round(sqrt($variance), 2);
    }

    private function calculateAcceleration(array $monthlyHistory): float
    {
        $scores = array_column($monthlyHistory, 'weighted_score');

        if (count($scores) < 6) {
            return 0.0;
        }

        $recent   = array_slice($scores, -3);     // months 10-12
        $previous = array_slice($scores, -6, 3);  // months 7-9

        return round(array_sum($recent) / 3 - array_sum($previous) / 3, 2);
    }

    private function deriveStabilityLabel(float $volatility): string
    {
        return match (true) {
            $volatility <= 5  => 'stable',
            $volatility <= 15 => 'fluctuating',
            default           => 'highly_unstable',
        };
    }

    protected function computeFragilityMetrics(float $riskScore, float $volatility, float $acceleration): array
    {
        $fragility = $this->calculateFragility($riskScore, $volatility, $acceleration);

        return [
            'fragility_index' => $fragility,
            'fragility_label' => $this->deriveFragilityLabel($fragility),
        ];
    }

    private function calculateFragility(float $riskScore, float $volatility, float $acceleration): float
    {
        return round(
            ($riskScore * 0.5) + ($volatility * 0.3) + (max($acceleration, 0) * 0.2),
            2
        );
    }

    private function deriveFragilityLabel(float $fragility): string
    {
        return match (true) {
            $fragility <= 30 => 'resilient',
            $fragility <= 60 => 'vulnerable',
            $fragility <= 80 => 'fragile',
            default          => 'critical',
        };
    }

    private function buildExecutiveSummary(string $riskLevel, string $trend, array $activeCategories): string
    {
        $level = strtoupper($riskLevel);

        if (empty($activeCategories)) {
            return "National risk posture is {$level} and {$trend}. Insufficient signal data for domain attribution.";
        }

        $ordered = collect($activeCategories)
            ->sortByDesc(fn ($d) => self::DOMAIN_WEIGHTS[$d] ?? 0)
            ->values()
            ->all();

        $categoryList = $this->formatCategoryList($ordered);

        return "National risk posture is {$level} and {$trend}, driven primarily by {$categoryList} signals.";
    }

    private function formatCategoryList(array $items): string
    {
        if (count($items) === 1) {
            return $items[0];
        }

        $last = array_pop($items);

        return implode(', ', $items) . ' and ' . $last;
    }
}
