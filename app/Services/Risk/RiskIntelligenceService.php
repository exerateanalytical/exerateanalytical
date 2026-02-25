<?php

namespace App\Services\Risk;

use App\Models\FiscalRiskSignal;
use App\Models\RiskSignal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RiskIntelligenceService
{
    private const SEVERITY_MAP = [
        'low'      => 25,
        'moderate' => 50,
        'high'     => 75,
        'critical' => 100,
    ];

    public function getNationalRiskSummary(string $countryId): array
    {
        $allSignals = $this->collectSignals($countryId);

        if ($allSignals->isEmpty()) {
            return [
                'national_risk_score' => 0.0,
                'risk_level'          => 'low',
                'trend'               => 'stable',
                'active_categories'   => [],
                'signal_count'        => 0,
                'last_updated'        => null,
            ];
        }

        $nationalRiskScore = round(
            $allSignals->avg(fn ($s) => $this->normalizeSeverity($s['severity'])),
            2
        );

        return [
            'national_risk_score' => $nationalRiskScore,
            'risk_level'          => $this->deriveRiskLevel($nationalRiskScore),
            'trend'               => $this->computeTrend($allSignals),
            'active_categories'   => $allSignals
                ->pluck('category')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'signal_count'        => $allSignals->count(),
            'last_updated'        => $allSignals
                ->pluck('triggered_at')
                ->filter()
                ->max()
                ?->toIso8601String(),
        ];
    }

    private function collectSignals(string $countryId): Collection
    {
        $signals = collect();

        RiskSignal::where('country_id', $countryId)
            ->get()
            ->each(function ($s) use ($signals) {
                $signals->push([
                    'severity'     => $s->severity,
                    'category'     => $s->module ?? 'Unknown',
                    'triggered_at' => $s->triggered_at,
                ]);
            });

        FiscalRiskSignal::where('country_id', $countryId)
            ->get()
            ->each(function ($s) use ($signals) {
                $signals->push([
                    'severity'     => $s->severity,
                    'category'     => $s->risk_type,
                    'triggered_at' => $s->triggered_at,
                ]);
            });

        return $signals;
    }

    private function normalizeSeverity(string $severity): int
    {
        return self::SEVERITY_MAP[strtolower($severity)] ?? 50;
    }

    private function deriveRiskLevel(float $score): string
    {
        return match (true) {
            $score <= 25 => 'low',
            $score <= 50 => 'moderate',
            $score <= 75 => 'high',
            default      => 'critical',
        };
    }

    private function computeTrend(Collection $allSignals): string
    {
        $now            = Carbon::now();
        $recentCutoff   = $now->copy()->subDays(30);
        $baselineStart  = $now->copy()->subDays(90);

        $recent = $allSignals->filter(
            fn ($s) => $s['triggered_at'] && $s['triggered_at']->gte($recentCutoff)
        );

        $baseline = $allSignals->filter(
            fn ($s) => $s['triggered_at']
                && $s['triggered_at']->lt($recentCutoff)
                && $s['triggered_at']->gte($baselineStart)
        );

        if ($baseline->isEmpty()) {
            return 'stable';
        }

        $recentAvg   = $recent->isEmpty()
            ? 0.0
            : $recent->avg(fn ($s) => $this->normalizeSeverity($s['severity']));

        $baselineAvg = $baseline->avg(fn ($s) => $this->normalizeSeverity($s['severity']));

        if ($recentAvg > $baselineAvg + 5) {
            return 'deteriorating';
        }

        if ($recentAvg < $baselineAvg - 5) {
            return 'improving';
        }

        return 'stable';
    }
}
