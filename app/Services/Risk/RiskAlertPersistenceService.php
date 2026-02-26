<?php

namespace App\Services\Risk;

use App\Models\Country;
use App\Models\RiskAlert;
use App\Models\RiskAlertEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RiskAlertPersistenceService
{
    public function __construct(
        private readonly RiskNotificationService $notificationService,
    ) {}

    /**
     * Sync evaluated alerts against the persistence layer for a country.
     *
     * - New alert                           → create alert + "triggered" event
     * - Existing alert, was inactive        → reactivate + "triggered" event
     * - Existing alert, already active      → update timestamps + "retriggered" event
     * - Existing alert not in evaluated set → deactivate + "resolved" event
     *
     * Returns all currently active alerts for the country.
     *
     * @param  string $countryId
     * @param  array<int, array{type: string, severity: string}> $evaluatedAlerts
     * @return array
     */
    public function sync(string $countryId, array $evaluatedAlerts): array
    {
        $now            = Carbon::now();
        $evaluatedTypes = collect($evaluatedAlerts)->pluck('type')->all();

        // Resolve federation region for the country (single value query)
        $regionId = Country::where('id', $countryId)->value('region_id');

        $existing = RiskAlert::where('country_id', $countryId)->get()->keyBy('type');

        foreach ($evaluatedAlerts as $alert) {
            if ($existing->has($alert['type'])) {
                $record    = $existing->get($alert['type']);
                $eventType = $record->active ? 'retriggered' : 'triggered';

                $record->update([
                    'active'            => true,
                    'severity'          => $alert['severity'],
                    'last_triggered_at' => $now,
                    'region_id'         => $regionId,
                ]);

                $event = RiskAlertEvent::create([
                    'risk_alert_id' => $record->id,
                    'country_id'    => $countryId,
                    'region_id'     => $regionId,
                    'type'          => $alert['type'],
                    'event_type'    => $eventType,
                    'severity'      => $alert['severity'],
                ]);

                $this->notificationService->notify($event);
            } else {
                $record = RiskAlert::create([
                    'country_id'         => $countryId,
                    'region_id'          => $regionId,
                    'type'               => $alert['type'],
                    'severity'           => $alert['severity'],
                    'active'             => true,
                    'first_triggered_at' => $now,
                    'last_triggered_at'  => $now,
                ]);

                $event = RiskAlertEvent::create([
                    'risk_alert_id' => $record->id,
                    'country_id'    => $countryId,
                    'region_id'     => $regionId,
                    'type'          => $alert['type'],
                    'event_type'    => 'triggered',
                    'severity'      => $alert['severity'],
                ]);

                $this->notificationService->notify($event);
            }
        }

        $existing
            ->reject(fn (RiskAlert $alert) => in_array($alert->type, $evaluatedTypes, true))
            ->each(function (RiskAlert $alert) use ($regionId) {
                $alert->update(['active' => false]);

                $event = RiskAlertEvent::create([
                    'risk_alert_id' => $alert->id,
                    'country_id'    => $alert->country_id,
                    'region_id'     => $regionId ?? $alert->region_id,
                    'type'          => $alert->type,
                    'event_type'    => 'resolved',
                    'severity'      => $alert->severity,
                ]);

                $this->notificationService->notify($event);
            });

        Cache::forget('risk:analytics:' . ($regionId ?? 'global') . ":{$countryId}");

        return RiskAlert::where('country_id', $countryId)
            ->where('active', true)
            ->get()
            ->toArray();
    }
}
