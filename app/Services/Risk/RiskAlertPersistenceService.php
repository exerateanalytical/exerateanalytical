<?php

namespace App\Services\Risk;

use App\Models\RiskAlert;
use Illuminate\Support\Carbon;

class RiskAlertPersistenceService
{
    /**
     * Sync evaluated alerts against the persistence layer for a country.
     *
     * - Existing alert with same type  → set active = true, update last_triggered_at
     * - No existing alert for type     → create new record
     * - Existing alert not in evaluated set → set active = false
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

        $existing = RiskAlert::where('country_id', $countryId)->get()->keyBy('type');

        foreach ($evaluatedAlerts as $alert) {
            if ($existing->has($alert['type'])) {
                $existing->get($alert['type'])->update([
                    'active'            => true,
                    'severity'          => $alert['severity'],
                    'last_triggered_at' => $now,
                ]);
            } else {
                RiskAlert::create([
                    'country_id'         => $countryId,
                    'type'               => $alert['type'],
                    'severity'           => $alert['severity'],
                    'active'             => true,
                    'first_triggered_at' => $now,
                    'last_triggered_at'  => $now,
                ]);
            }
        }

        $existing
            ->reject(fn (RiskAlert $alert) => in_array($alert->type, $evaluatedTypes, true))
            ->each(fn (RiskAlert $alert) => $alert->update(['active' => false]));

        return RiskAlert::where('country_id', $countryId)
            ->where('active', true)
            ->get()
            ->toArray();
    }
}
