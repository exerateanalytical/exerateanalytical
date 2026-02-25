<?php

namespace App\Services\Risk;

use App\Models\Region;
use App\Models\RiskSignal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RegionalRiskIntelligenceService extends RiskIntelligenceService
{
    public function getRegionalRiskRanking(string $countryId): array
    {
        return Region::where('country_id', $countryId)
            ->get()
            ->map(function ($region) use ($countryId) {
                $summary = $this->computeRegionalSummary($countryId, $region->id);

                return array_merge(
                    ['region_id' => $region->id, 'name' => $region->name],
                    $summary
                );
            })
            ->sortByDesc('regional_risk_score')
            ->values()
            ->all();
    }

    public function getRegionalRiskDetail(string $countryId, string $regionId): array
    {
        return Cache::remember(
            "risk:regional:{$countryId}:{$regionId}",
            900,
            fn () => $this->computeRegionalSummary($countryId, $regionId)
        );
    }

    private function computeRegionalSummary(string $countryId, string $regionId): array
    {
        $windowStart = Carbon::now()->subMonths(12);
        $domains     = $this->collectRegionalDomains($countryId, $regionId, $windowStart);

        $allSignals = $domains['governance']
            ->concat($domains['fiscal'])
            ->concat($domains['accountability']);

        if ($allSignals->isEmpty()) {
            return [
                'regional_risk_score' => 0.0,
                'risk_level'          => 'low',
                'trend'               => 'stable',
                'domain_averages'     => [
                    'governance'     => 0.0,
                    'fiscal'         => 0.0,
                    'accountability' => 0.0,
                ],
                'signal_count'        => 0,
            ];
        }

        $score = round($this->weightedScore($domains), 2);
        $level = $this->deriveRiskLevel($score);
        $trend = $this->computeTrend($domains);

        return [
            'regional_risk_score' => $score,
            'risk_level'          => $level,
            'trend'               => $trend,
            'domain_averages'     => [
                'governance'     => round($this->domainAvg($domains['governance']), 2),
                'fiscal'         => round($this->domainAvg($domains['fiscal']), 2),
                'accountability' => round($this->domainAvg($domains['accountability']), 2),
            ],
            'signal_count'        => $allSignals->count(),
        ];
    }

    private function collectRegionalDomains(string $countryId, string $regionId, Carbon $windowStart): array
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

        // FiscalRiskSignal carries no region_id; excluded to avoid polluting regional scores
        // with national-level fiscal signals.
        return [
            'governance'     => $governance,
            'fiscal'         => collect(),
            'accountability' => $accountability,
        ];
    }
}
