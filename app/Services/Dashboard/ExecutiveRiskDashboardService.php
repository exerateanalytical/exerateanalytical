<?php

namespace App\Services\Dashboard;

use App\Models\Country;
use App\Models\FiscalRiskSignal;
use App\Models\RiskSignal;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ExecutiveRiskDashboardService
{
    private const SEVERITY_MAP = [
        'low'      => 25,
        'moderate' => 50,
        'high'     => 75,
        'critical' => 100,
    ];

    private const DOMAIN_WEIGHTS = [
        'governance'     => 0.4,
        'fiscal'         => 0.35,
        'accountability' => 0.25,
    ];

    public function __construct(private readonly RiskIntelligenceService $riskService) {}

    public function getRiskRanking(): array
    {
        return Country::active()->get()
            ->map(fn ($country) => array_merge(
                [
                    'country_id' => $country->id,
                    'name'       => $country->name,
                    'iso_code'   => $country->iso_code,
                ],
                $this->riskService->getNationalRiskSummary($country->id)
            ))
            ->sortByDesc('national_risk_score')
            ->values()
            ->all();
    }

    public function getRiskDrivers(string $countryId): array
    {
        $windowStart = Carbon::now()->subMonths(12);
        $domains     = $this->collectDomains($countryId, $windowStart);

        return [
            'country_id'     => $countryId,
            'governance'     => round($this->domainAvg($domains['governance']), 2),
            'fiscal'         => round($this->domainAvg($domains['fiscal']), 2),
            'accountability' => round($this->domainAvg($domains['accountability']), 2),
            'weights'        => self::DOMAIN_WEIGHTS,
        ];
    }

    public function getRiskHistory(string $countryId): array
    {
        $windowStart = Carbon::now()->subMonths(12);
        $domains     = $this->collectDomains($countryId, $windowStart);

        $months = [];

        for ($i = 11; $i >= 0; $i--) {
            $from   = Carbon::now()->startOfMonth()->subMonths($i);
            $before = $from->copy()->addMonth();
            $sliced = $this->sliceDomains($domains, $from, $before);

            $months[] = [
                'month'          => $from->format('Y-m'),
                'weighted_score' => round($this->weightedScore($sliced), 2),
            ];
        }

        return $months;
    }

    public function getAlertWatchlist(): array
    {
        return Country::active()->get()
            ->map(fn ($country) => array_merge(
                [
                    'country_id' => $country->id,
                    'name'       => $country->name,
                    'iso_code'   => $country->iso_code,
                ],
                $this->riskService->getNationalRiskSummary($country->id)
            ))
            ->filter(fn ($entry) =>
                $entry['risk_level'] === 'critical'
                || ($entry['trend'] === 'deteriorating' && $entry['national_risk_score'] > 60)
            )
            ->values()
            ->all();
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

    private function sliceDomains(array $domains, Carbon $from, Carbon $before): array
    {
        $slice = fn (Collection $signals) => $signals->filter(
            fn ($s) => $s['triggered_at']
                && $s['triggered_at']->gte($from)
                && $s['triggered_at']->lt($before)
        );

        return [
            'governance'     => $slice($domains['governance']),
            'fiscal'         => $slice($domains['fiscal']),
            'accountability' => $slice($domains['accountability']),
        ];
    }

    private function weightedScore(array $domains): float
    {
        return ($this->domainAvg($domains['governance'])    * self::DOMAIN_WEIGHTS['governance'])
            + ($this->domainAvg($domains['fiscal'])         * self::DOMAIN_WEIGHTS['fiscal'])
            + ($this->domainAvg($domains['accountability']) * self::DOMAIN_WEIGHTS['accountability']);
    }

    private function domainAvg(Collection $signals): float
    {
        if ($signals->isEmpty()) {
            return 0.0;
        }

        return $signals->avg(fn ($s) => self::SEVERITY_MAP[strtolower($s['severity'])] ?? 50);
    }
}
