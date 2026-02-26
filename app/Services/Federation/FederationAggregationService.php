<?php

namespace App\Services\Federation;

use App\Models\FederatedGlobalSnapshot;
use App\Models\FederationRegion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class FederationAggregationService
{
    public function __construct(
        private readonly RegionalSystemicSnapshotService $regionalService,
    ) {}

    /**
     * Aggregate the latest regional snapshots into a global federated snapshot
     * and persist the result.
     */
    public function computeGlobal(): FederatedGlobalSnapshot
    {
        $regionIds = FederationRegion::pluck('id');

        $snapshots = $regionIds
            ->map(fn (string $id) => $this->regionalService->latestForRegion($id))
            ->filter();

        $regionCount = $snapshots->count();

        // Sum numeric payload fields across all regions
        $totals = [
            'active_alerts'         => 0,
            'unacknowledged_alerts' => 0,
            'escalated_24h'         => 0,
            'resolved_24h'          => 0,
            'country_count'         => 0,
            'by_severity'           => ['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0],
        ];

        foreach ($snapshots as $snapshot) {
            $p = $snapshot->payload;

            $totals['active_alerts']         += (int) ($p['active_alerts'] ?? 0);
            $totals['unacknowledged_alerts'] += (int) ($p['unacknowledged_alerts'] ?? 0);
            $totals['escalated_24h']         += (int) ($p['escalated_24h'] ?? 0);
            $totals['resolved_24h']          += (int) ($p['resolved_24h'] ?? 0);
            $totals['country_count']         += (int) ($p['country_count'] ?? 0);

            foreach (['low', 'medium', 'high', 'critical'] as $sev) {
                $totals['by_severity'][$sev] += (int) ($p['by_severity'][$sev] ?? 0);
            }
        }

        $payload = array_merge($totals, ['region_count' => $regionCount]);

        $snapshot = FederatedGlobalSnapshot::create([
            'snapshot_at'  => Carbon::now(),
            'region_count' => $regionCount,
            'payload'      => $payload,
        ]);

        Cache::forget('federation:snapshot:global');

        return $snapshot;
    }

    /**
     * Return the most recent global snapshot (5-minute cache).
     */
    public function latestGlobal(): ?FederatedGlobalSnapshot
    {
        return Cache::remember(
            'federation:snapshot:global',
            now()->addMinutes(5),
            fn () => FederatedGlobalSnapshot::latest('snapshot_at')->first()
        );
    }
}
