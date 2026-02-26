<?php

namespace App\Services\Federation;

use App\Models\Country;
use App\Models\FederationRegion;
use App\Models\RegionalSystemicSnapshot;
use App\Models\RiskAlert;
use App\Models\RiskAlertEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RegionalSystemicSnapshotService
{
    /**
     * Generate and persist a new snapshot for the given federation region.
     */
    public function generate(string $regionId): RegionalSystemicSnapshot
    {
        $now = Carbon::now();

        $regionCode = FederationRegion::where('id', $regionId)->value('code');

        // Active alert counts grouped by severity (single query)
        $activeBySeverity = RiskAlert::where('region_id', $regionId)
            ->where('active', true)
            ->selectRaw('severity, count(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        // Unacknowledged active alerts
        $unacknowledgedCount = RiskAlert::where('region_id', $regionId)
            ->where('active', true)
            ->whereNull('acknowledged_at')
            ->count();

        // Escalated and resolved in the last 24 hours (single query)
        $recentEventCounts = RiskAlertEvent::where('region_id', $regionId)
            ->where('created_at', '>=', $now->copy()->subHours(24))
            ->whereIn('event_type', ['escalated', 'resolved'])
            ->selectRaw('event_type, count(*) as total')
            ->groupBy('event_type')
            ->pluck('total', 'event_type');

        // Total countries in this region
        $countryCount = Country::where('region_id', $regionId)->count();

        $payload = [
            'active_alerts'         => $activeBySeverity->sum(),
            'by_severity'           => [
                'low'      => (int) ($activeBySeverity->get('low', 0)),
                'medium'   => (int) ($activeBySeverity->get('medium', 0)),
                'high'     => (int) ($activeBySeverity->get('high', 0)),
                'critical' => (int) ($activeBySeverity->get('critical', 0)),
            ],
            'unacknowledged_alerts' => $unacknowledgedCount,
            'escalated_24h'         => (int) ($recentEventCounts->get('escalated', 0)),
            'resolved_24h'          => (int) ($recentEventCounts->get('resolved', 0)),
            'country_count'         => $countryCount,
        ];

        $snapshot = RegionalSystemicSnapshot::create([
            'region_id'   => $regionId,
            'region_code' => $regionCode,
            'snapshot_at' => $now,
            'payload'     => $payload,
        ]);

        Cache::forget("federation:snapshot:region:{$regionId}");
        Cache::forget('federation:snapshot:all-regions');

        return $snapshot;
    }

    /**
     * Return the most recent snapshot for a region (5-minute cache).
     */
    public function latestForRegion(string $regionId): ?RegionalSystemicSnapshot
    {
        return Cache::remember(
            "federation:snapshot:region:{$regionId}",
            now()->addMinutes(5),
            fn () => RegionalSystemicSnapshot::where('region_id', $regionId)
                ->latest('snapshot_at')
                ->first()
        );
    }

    /**
     * Return the latest snapshot for every federation region (5-minute cache).
     *
     * @return \Illuminate\Support\Collection<int, RegionalSystemicSnapshot|null>
     */
    public function allRegionsLatest(): \Illuminate\Support\Collection
    {
        return Cache::remember(
            'federation:snapshot:all-regions',
            now()->addMinutes(5),
            function () {
                $regionIds = FederationRegion::pluck('id');

                return $regionIds->map(
                    fn (string $id) => RegionalSystemicSnapshot::where('region_id', $id)
                        ->latest('snapshot_at')
                        ->first()
                )->filter();
            }
        );
    }
}
