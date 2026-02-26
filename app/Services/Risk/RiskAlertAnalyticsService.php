<?php

namespace App\Services\Risk;

use App\Models\RiskAlert;
use App\Models\RiskAlertEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RiskAlertAnalyticsService
{
    /**
     * Compute alert analytics metrics for a country.
     * Read-only. Results cached for 5 minutes; invalidated by persistence/escalation.
     */
    public function metrics(string $countryId): array
    {
        return Cache::remember("risk:analytics:{$countryId}", now()->addMinutes(5), function () use ($countryId) {
            // ── Event counts (one grouped query, replacing four separate counts) ──
            $eventCounts = RiskAlertEvent::where('country_id', $countryId)
                ->whereIn('event_type', ['triggered', 'retriggered', 'resolved', 'acknowledged'])
                ->selectRaw('event_type, count(*) as total')
                ->groupBy('event_type')
                ->pluck('total', 'event_type');

            $retriggeredCount  = (int) ($eventCounts['retriggered']  ?? 0);
            $resolvedCount     = (int) ($eventCounts['resolved']     ?? 0);
            $acknowledgedCount = (int) ($eventCounts['acknowledged'] ?? 0);
            $triggeredCount    = (int) ($eventCounts['triggered']    ?? 0) + $retriggeredCount;

            // ── Mean time to acknowledge ──────────────────────────────────────
            $ackAlerts = RiskAlert::where('country_id', $countryId)
                ->whereNotNull('acknowledged_at')
                ->get(['first_triggered_at', 'acknowledged_at']);

            $mtta = $ackAlerts->isEmpty()
                ? 0.0
                : round(
                    $ackAlerts->avg(
                        fn ($a) => $a->first_triggered_at->diffInMinutes($a->acknowledged_at)
                    ),
                    2
                );

            // ── Mean time to resolve ──────────────────────────────────────────
            $resolvedEvents = RiskAlertEvent::where('country_id', $countryId)
                ->where('event_type', 'resolved')
                ->get(['risk_alert_id', 'created_at']);

            if ($resolvedEvents->isEmpty()) {
                $mttr = 0.0;
            } else {
                $alertMap = RiskAlert::whereIn(
                    'id',
                    $resolvedEvents->pluck('risk_alert_id')->unique()->all()
                )
                    ->get(['id', 'first_triggered_at'])
                    ->keyBy('id');

                $diffs = $resolvedEvents
                    ->map(function ($event) use ($alertMap) {
                        $alert = $alertMap->get($event->risk_alert_id);

                        return $alert
                            ? $alert->first_triggered_at->diffInMinutes($event->created_at)
                            : null;
                    })
                    ->filter()
                    ->values();

                $mttr = $diffs->isEmpty() ? 0.0 : round($diffs->avg(), 2);
            }

            // ── Retrigger rate ────────────────────────────────────────────────
            $retriggeredRate = round($retriggeredCount / max(1, $triggeredCount), 4);

            // ── Flapping score ────────────────────────────────────────────────
            $windowStart = Carbon::now()->subDays(30);

            $flappingScore = RiskAlertEvent::where('country_id', $countryId)
                ->where('created_at', '>=', $windowStart)
                ->whereIn('event_type', ['triggered', 'resolved'])
                ->get(['risk_alert_id', 'event_type'])
                ->groupBy('risk_alert_id')
                ->filter(
                    fn ($events) => $events->where('event_type', 'triggered')->count() >= 2
                        && $events->where('event_type', 'resolved')->count() >= 1
                )
                ->count();

            return [
                'total_triggered'                  => $triggeredCount,
                'total_resolved'                   => $resolvedCount,
                'total_acknowledged'               => $acknowledgedCount,
                'mean_time_to_acknowledge_minutes' => $mtta,
                'mean_time_to_resolve_minutes'     => $mttr,
                'retrigger_rate'                   => $retriggeredRate,
                'flapping_score'                   => $flappingScore,
            ];
        });
    }
}
