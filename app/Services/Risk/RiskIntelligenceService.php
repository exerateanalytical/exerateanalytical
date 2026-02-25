<?php

namespace App\Services\Risk;

use App\Models\FiscalRiskSignal;
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
                'fragility_index'     => 0.0,
                'fragility_label'     => 'resilient',
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
        $volatilityMetrics  = $this->computeVolatilityMetrics($this->computeMonthlyScores($domains));
        $fragilityMetrics   = $this->computeFragilityMetrics(
            $nationalRiskScore,
            $volatilityMetrics['volatility_index'],
            $volatilityMetrics['acceleration']
        );

        return [
            'national_risk_score' => $nationalRiskScore,
            'risk_level'          => $riskLevel,
            'trend'               => $trend,
            'active_categories'   => $activeCategories,
            'signal_count'        => $signalCount,
            'last_updated'        => $allSignals
                ->pluck('triggered_at')
                ->filter()
                ->max()
                ?->toIso8601String(),
            'confidence'          => $signalCount >= 3 ? 'normal' : 'low',
            'executive_summary'   => $this->buildExecutiveSummary($riskLevel, $trend, $activeCategories),
            'volatility_index'    => $volatilityMetrics['volatility_index'],
            'acceleration'        => $volatilityMetrics['acceleration'],
            'stability_label'     => $volatilityMetrics['stability_label'],
            'fragility_index'     => $fragilityMetrics['fragility_index'],
            'fragility_label'     => $fragilityMetrics['fragility_label'],
        ];
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
