<?php

namespace App\Services\Risk;

use App\Models\RiskAlert;
use App\Models\RiskAlertEvent;

class RiskGovernanceMetricsService
{
    public function __construct(
        private readonly RiskAlertAnalyticsService $analyticsService,
    ) {}

    /**
     * Compute SLA compliance and governance stability metrics for a country.
     * Read-only. No raw SQL. No N+1 queries.
     */
    public function metrics(string $countryId): array
    {
        $thresholds = config('risk_escalation.thresholds');

        // ── acknowledgement_sla_rate ─────────────────────────────────────
        $ackedAlerts = RiskAlert::where('country_id', $countryId)
            ->whereNotNull('acknowledged_at')
            ->get(['id', 'severity', 'first_triggered_at', 'acknowledged_at']);

        if ($ackedAlerts->isEmpty()) {
            $ackSlaRate = 0.0;
        } else {
            $ackedIds = $ackedAlerts->pluck('id')->all();

            // One query: first 'triggered' event per alert → original severity
            $origSeverityForAcked = RiskAlertEvent::whereIn('risk_alert_id', $ackedIds)
                ->where('event_type', 'triggered')
                ->orderBy('created_at')
                ->get(['risk_alert_id', 'severity'])
                ->groupBy('risk_alert_id')
                ->map(fn ($events) => $events->first()->severity);

            $compliant = $ackedAlerts->filter(function ($alert) use ($thresholds, $origSeverityForAcked) {
                $origSeverity = $origSeverityForAcked->get($alert->id, $alert->severity);
                $threshold    = $thresholds[$origSeverity] ?? null;

                // null threshold (critical ceiling) → no SLA breach possible
                if ($threshold === null) {
                    return true;
                }

                return $alert->first_triggered_at->diffInMinutes($alert->acknowledged_at) <= $threshold;
            })->count();

            $ackSlaRate = round($compliant / $ackedAlerts->count(), 4);
        }

        // ── resolution_sla_rate ──────────────────────────────────────────
        $resolvedEvents = RiskAlertEvent::where('country_id', $countryId)
            ->where('event_type', 'resolved')
            ->get(['risk_alert_id', 'created_at']);

        if ($resolvedEvents->isEmpty()) {
            $resSlaRate = 0.0;
        } else {
            $resolvedAlertIds = $resolvedEvents->pluck('risk_alert_id')->unique()->all();

            $resolvedAlerts = RiskAlert::whereIn('id', $resolvedAlertIds)
                ->get(['id', 'severity', 'first_triggered_at'])
                ->keyBy('id');

            $origSeverityForResolved = RiskAlertEvent::whereIn('risk_alert_id', $resolvedAlertIds)
                ->where('event_type', 'triggered')
                ->orderBy('created_at')
                ->get(['risk_alert_id', 'severity'])
                ->groupBy('risk_alert_id')
                ->map(fn ($events) => $events->first()->severity);

            $compliant = $resolvedEvents->filter(function ($event) use ($resolvedAlerts, $thresholds, $origSeverityForResolved) {
                $alert = $resolvedAlerts->get($event->risk_alert_id);

                if (! $alert) {
                    return false;
                }

                $origSeverity = $origSeverityForResolved->get($alert->id, $alert->severity);
                $threshold    = $thresholds[$origSeverity] ?? null;

                if ($threshold === null) {
                    return true;
                }

                return $alert->first_triggered_at->diffInMinutes($event->created_at) <= $threshold;
            })->count();

            $resSlaRate = round($compliant / $resolvedEvents->count(), 4);
        }

        // ── escalation_rate ──────────────────────────────────────────────
        $totalAlerts = RiskAlert::where('country_id', $countryId)->count();

        $escalatedAlertIds = RiskAlertEvent::where('country_id', $countryId)
            ->where('event_type', 'escalated')
            ->pluck('risk_alert_id')
            ->unique();

        $escalationRate = $totalAlerts > 0
            ? round($escalatedAlertIds->count() / $totalAlerts, 4)
            : 0.0;

        // ── mean_escalation_time_minutes ─────────────────────────────────
        if ($escalatedAlertIds->isEmpty()) {
            $meanEscTime = 0.0;
        } else {
            $escalatedAlertIdsArray = $escalatedAlertIds->all();

            // First escalated event per alert (ordered ascending)
            $firstEscEvents = RiskAlertEvent::whereIn('risk_alert_id', $escalatedAlertIdsArray)
                ->where('event_type', 'escalated')
                ->orderBy('created_at')
                ->get(['risk_alert_id', 'created_at'])
                ->groupBy('risk_alert_id')
                ->map(fn ($events) => $events->first());

            $escalatedAlerts = RiskAlert::whereIn('id', $escalatedAlertIdsArray)
                ->get(['id', 'first_triggered_at'])
                ->keyBy('id');

            $diffs = $firstEscEvents
                ->map(function ($event) use ($escalatedAlerts) {
                    $alert = $escalatedAlerts->get($event->risk_alert_id);

                    return $alert
                        ? $alert->first_triggered_at->diffInMinutes($event->created_at)
                        : null;
                })
                ->filter()
                ->values();

            $meanEscTime = $diffs->isEmpty() ? 0.0 : round($diffs->avg(), 2);
        }

        // ── governance_stability_score ───────────────────────────────────
        $analyticsMetrics = $this->analyticsService->metrics($countryId);
        $flappingRatio    = $analyticsMetrics['flapping_score'] / max(1, $totalAlerts);

        $base = ($ackSlaRate * 0.4)
              + ($resSlaRate * 0.3)
              + ((1 - $escalationRate) * 0.2)
              + ((1 - $flappingRatio) * 0.1);

        $governanceScore = round(max(0.0, min(100.0, $base * 100)), 2);

        return [
            'acknowledgement_sla_rate'     => $ackSlaRate,
            'resolution_sla_rate'          => $resSlaRate,
            'escalation_rate'              => $escalationRate,
            'mean_escalation_time_minutes' => $meanEscTime,
            'governance_stability_score'   => $governanceScore,
        ];
    }
}
