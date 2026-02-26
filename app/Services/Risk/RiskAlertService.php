<?php

namespace App\Services\Risk;

class RiskAlertService
{
    /**
     * Evaluate a national risk summary and return zero or more alerts.
     * Pure function: no DB writes, no caching changes.
     *
     * @param  array $summary  Return value of RiskIntelligenceService::getNationalRiskSummary()
     * @return array<int, array{type: string, severity: string}>
     */
    public function evaluate(array $summary): array
    {
        $alerts = [];

        if ($summary['regime_shift_detected'] === true) {
            $alerts[] = [
                'type'     => 'regime_shift',
                'severity' => 'high',
            ];
        }

        if ($summary['projection_trend'] === 'rising'
            && in_array($summary['risk_level'], ['high', 'critical'], true)) {
            $alerts[] = [
                'type'     => 'projection_deterioration',
                'severity' => 'high',
            ];
        }

        if ($summary['fragility_label'] === 'critical') {
            $alerts[] = [
                'type'     => 'fragility_critical',
                'severity' => 'high',
            ];
        }

        if ($summary['risk_concentration_index'] > 0.6) {
            $alerts[] = [
                'type'     => 'risk_concentration_spike',
                'severity' => 'medium',
            ];
        }

        return $alerts;
    }
}
