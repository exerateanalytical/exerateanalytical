<?php

namespace App\Services\Risk;

use App\Models\RiskAlert;
use App\Models\RiskAlertEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RiskEscalationService
{
    public function __construct(
        private readonly RiskNotificationService $notificationService,
    ) {}

    /**
     * Escalate all active, unacknowledged alerts that have exceeded
     * their severity threshold.
     *
     * @return int Number of alerts escalated.
     */
    public function run(): int
    {
        $escalated = 0;
        $now       = Carbon::now();

        RiskAlert::where('active', true)
            ->whereNull('acknowledged_at')
            ->chunk(200, function ($alerts) use (&$escalated, $now) {
                foreach ($alerts as $alert) {
                    $threshold = config("risk_escalation.thresholds.{$alert->severity}");

                    if ($threshold === null) {
                        continue;
                    }

                    if ($alert->first_triggered_at->diffInMinutes($now) < $threshold) {
                        continue;
                    }

                    $newSeverity = $this->nextSeverity($alert->severity);

                    if ($newSeverity === null) {
                        continue;
                    }

                    $alert->update(['severity' => $newSeverity]);

                    $event = RiskAlertEvent::create([
                        'risk_alert_id' => $alert->id,
                        'country_id'    => $alert->country_id,
                        'type'          => $alert->type,
                        'event_type'    => 'escalated',
                        'severity'      => $newSeverity,
                    ]);

                    $this->notificationService->notify($event);

                    Cache::forget("risk:analytics:{$alert->country_id}");

                    $escalated++;
                }
            });

        return $escalated;
    }

    private function nextSeverity(string $severity): ?string
    {
        return match ($severity) {
            'medium' => 'high',
            'high'   => 'critical',
            default  => null,
        };
    }
}
