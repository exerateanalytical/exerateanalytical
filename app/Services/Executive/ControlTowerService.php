<?php

namespace App\Services\Executive;

use App\Models\FederatedGlobalSnapshot;
use App\Models\RegionalSystemicSnapshot;
use App\Models\RepresentationMetric;
use App\Models\RiskContagionRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ControlTowerService
{
    private const CACHE_KEY = 'executive.control_tower';
    private const CACHE_TTL = 60; // seconds

    /**
     * Build and cache the control tower payload.
     * Falls back to a null/empty skeleton if aggregation fails.
     */
    public function build(): array
    {
        try {
            return Cache::remember(
                self::CACHE_KEY,
                now()->addSeconds(self::CACHE_TTL),
                fn () => $this->aggregate(),
            );
        } catch (\Throwable) {
            return $this->emptyPayload();
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Core aggregation  (≤ 6 DB round-trips)
    // ─────────────────────────────────────────────────────────────────────────

    private function aggregate(): array
    {
        $cutoff24 = now()->subHours(24)->toDateTimeString();
        $cutoff48 = now()->subHours(48)->toDateTimeString();

        // Q1 — Latest global federated snapshot (hero source)
        $globalSnapshot = null;
        try {
            $globalSnapshot = FederatedGlobalSnapshot::latest('snapshot_at')
                ->select('payload', 'region_count', 'snapshot_at')
                ->first();
        } catch (\Throwable) {}

        // Q2 — All civic counts + trust momentum in one round-trip
        $civicRow = null;
        try {
            $civicRow = DB::selectOne(
                "SELECT
                    (SELECT COUNT(*) FROM poll_votes        WHERE created_at >= ?) AS poll_votes_24h,
                    (SELECT COUNT(*) FROM petition_signatures WHERE created_at >= ?) AS sigs_24h,
                    (SELECT COUNT(*) FROM polls             WHERE created_at >= ?) AS new_polls_24h,
                    (SELECT COUNT(*) FROM petitions         WHERE created_at >= ?) AS new_petitions_24h,
                    (SELECT COUNT(*) FROM policy_proposals  WHERE created_at >= ?) AS new_policies_24h,
                    (SELECT COUNT(*) FROM reactions         WHERE created_at >= ?) AS reactions_24h,
                    (SELECT COUNT(*) FROM reactions
                        WHERE created_at >= ? AND created_at < ?)                  AS reactions_prev_24h,
                    (SELECT COALESCE(AVG(delta), 0) FROM reputation_events
                        WHERE created_at >= ?)                                     AS trust_momentum",
                [$cutoff24, $cutoff24, $cutoff24, $cutoff24, $cutoff24,
                 $cutoff24, $cutoff48, $cutoff24, $cutoff24],
            );
        } catch (\Throwable) {}

        // Q3 — Policy radar: top-5 engaged items across polls / petitions / policies
        $radarRows = [];
        try {
            $radarRows = DB::select(
                "SELECT id, title, 'poll' AS item_type,
                    (COALESCE(r.cnt, 0) + COALESCE(total_votes, 0) * 0.6) AS score
                 FROM polls
                 LEFT JOIN (
                     SELECT reactable_id, COUNT(*) AS cnt
                     FROM reactions WHERE reactable_type = ?
                     GROUP BY reactable_id
                 ) r ON r.reactable_id = polls.id
                 WHERE polls.status != 'restricted'

                 UNION ALL

                 SELECT id, title, 'petition' AS item_type,
                    (COALESCE(r.cnt, 0) + COALESCE(signature_count, 0) * 0.7) AS score
                 FROM petitions
                 LEFT JOIN (
                     SELECT reactable_id, COUNT(*) AS cnt
                     FROM reactions WHERE reactable_type = ?
                     GROUP BY reactable_id
                 ) r ON r.reactable_id = petitions.id
                 WHERE petitions.status != 'restricted'

                 UNION ALL

                 SELECT id, title, 'policy' AS item_type,
                    COALESCE(r.cnt, 0) AS score
                 FROM policy_proposals
                 LEFT JOIN (
                     SELECT reactable_id, COUNT(*) AS cnt
                     FROM reactions WHERE reactable_type = ?
                     GROUP BY reactable_id
                 ) r ON r.reactable_id = policy_proposals.id
                 WHERE policy_proposals.status != 'restricted'

                 ORDER BY score DESC
                 LIMIT 5",
                [\App\Models\Poll::class, \App\Models\Petition::class, \App\Models\PolicyProposal::class],
            );
        } catch (\Throwable) {}

        // Q4 — Latest regional systemic snapshot per region (correlated subquery)
        $regionSnapshots = collect();
        try {
            $regionSnapshots = RegionalSystemicSnapshot::whereRaw(
                'snapshot_at = (
                    SELECT MAX(s2.snapshot_at)
                    FROM regional_systemic_snapshots s2
                    WHERE s2.region_id = regional_systemic_snapshots.region_id
                )'
            )->select('region_id', 'region_code', 'payload')->get();
        } catch (\Throwable) {}

        // Q5 — Latest contagion run
        $contagionRun = null;
        try {
            $contagionRun = RiskContagionRun::latest('executed_at')
                ->select('cascade_index', 'iteration_count', 'parameters', 'executed_at')
                ->first();
        } catch (\Throwable) {}

        // Q6 — Latest representation metric per region (correlated subquery)
        $representations = collect();
        try {
            $representations = RepresentationMetric::whereRaw(
                'calculated_at = (
                    SELECT MAX(rm2.calculated_at)
                    FROM representation_metrics rm2
                    WHERE rm2.region_id = representation_metrics.region_id
                )'
            )->select('region_id', 'participation_rate', 'representation_gap')->get();
        } catch (\Throwable) {}

        return [
            'hero'                 => $this->buildHero($globalSnapshot, $civicRow),
            'regions'              => $this->buildRegions($regionSnapshots),
            'policy_radar'         => $this->buildPolicyRadar($radarRows),
            'civic_momentum'       => $this->buildCivicMomentum($civicRow),
            'contagion_forecast'   => $this->buildContagionForecast($contagionRun),
            'representation_index' => $this->buildRepresentationIndex($representations),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Section builders — each is independently try/catch guarded
    // ─────────────────────────────────────────────────────────────────────────

    private function buildHero(?FederatedGlobalSnapshot $snapshot, ?object $row): array
    {
        try {
            $payload  = $snapshot?->payload ?? [];
            $active   = (int) ($payload['active_alerts'] ?? 0);
            $bySev    = $payload['by_severity'] ?? [];
            $critical = (int) ($bySev['critical'] ?? 0);
            $high     = (int) ($bySev['high'] ?? 0);

            // Severity-weighted anomaly score (0.0 – 1.0)
            // Critical alerts weigh fully; high alerts weigh 60%.
            $anomaly = min(1.0, ($critical + $high * 0.6) / max(1, $active * 2 + 1));

            $stability     = round(max(0.0, 100 - ($anomaly * 100)), 2);
            $trustMomentum = round((float) ($row->trust_momentum ?? 0), 3);
            $civicDelta    = (int) ($row->poll_votes_24h ?? 0) + (int) ($row->sigs_24h ?? 0);

            $riskLevel = match (true) {
                $anomaly < 0.2 => 'LOW',
                $anomaly < 0.5 => 'MEDIUM',
                default        => 'HIGH',
            };

            return [
                'national_stability'       => $stability,
                'trust_momentum'           => $trustMomentum,
                'policy_risk_level'        => $riskLevel,
                'civic_activity_delta_24h' => $civicDelta,
            ];
        } catch (\Throwable) {
            return [
                'national_stability'       => null,
                'trust_momentum'           => null,
                'policy_risk_level'        => null,
                'civic_activity_delta_24h' => null,
            ];
        }
    }

    private function buildRegions(Collection $snapshots): array
    {
        try {
            return $snapshots->map(function (RegionalSystemicSnapshot $s): array {
                $p      = $s->payload ?? [];
                $active = (int) ($p['active_alerts'] ?? 0);
                $bySev  = $p['by_severity'] ?? [];

                // Weighted risk score: critical=4, high=3, medium=2, low=1 → normalised 0–100
                $weighted = (int) ($bySev['critical'] ?? 0) * 4
                          + (int) ($bySev['high']     ?? 0) * 3
                          + (int) ($bySev['medium']   ?? 0) * 2
                          + (int) ($bySev['low']      ?? 0);
                $meanRisk = round(min(100.0, $weighted / max(1, $active * 4) * 100), 2);

                // Fragility: fraction of active alerts that are unacknowledged (0.0 – 1.0)
                $unack     = (int) ($p['unacknowledged_alerts'] ?? 0);
                $fragility = round(min(1.0, $unack / max(1, $active)), 3);

                return [
                    'region_id'            => $s->region_id,
                    'region_code'          => $s->region_code,
                    'mean_risk_score'      => $meanRisk,
                    'mean_fragility_index' => $fragility,
                    'active_alert_count'   => $active,
                ];
            })->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildPolicyRadar(array $rows): array
    {
        try {
            return array_map(fn (object $r): array => [
                'id'    => $r->id,
                'title' => $r->title,
                'type'  => $r->item_type,
                'score' => round((float) $r->score, 2),
            ], $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    private function buildCivicMomentum(?object $row): array
    {
        try {
            if (! $row) {
                return ['new_polls_24h' => 0, 'new_petitions_24h' => 0, 'new_policies_24h' => 0, 'reaction_velocity' => null];
            }

            $prev     = max(1, (int) ($row->reactions_prev_24h ?? 1));
            $current  = (int) ($row->reactions_24h ?? 0);

            return [
                'new_polls_24h'     => (int) ($row->new_polls_24h ?? 0),
                'new_petitions_24h' => (int) ($row->new_petitions_24h ?? 0),
                'new_policies_24h'  => (int) ($row->new_policies_24h ?? 0),
                'reaction_velocity' => round($current / $prev, 3),
            ];
        } catch (\Throwable) {
            return ['new_polls_24h' => 0, 'new_petitions_24h' => 0, 'new_policies_24h' => 0, 'reaction_velocity' => null];
        }
    }

    private function buildContagionForecast(?RiskContagionRun $run): ?array
    {
        if (! $run) {
            return null;
        }

        try {
            return [
                'cascade_index'    => round((float) $run->cascade_index, 4),
                'iteration_count'  => (int) $run->iteration_count,
                'origin_region_id' => $run->parameters['region_id'] ?? null,
                'executed_at'      => $run->executed_at?->toIso8601String(),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function buildRepresentationIndex(Collection $metrics): array
    {
        try {
            return $metrics->map(fn (RepresentationMetric $m): array => [
                'region_id'          => $m->region_id,
                'participation_rate' => (float) $m->participation_rate,
                'representation_gap' => (float) $m->representation_gap,
            ])->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function emptyPayload(): array
    {
        return [
            'hero'                 => null,
            'regions'              => [],
            'policy_radar'         => [],
            'civic_momentum'       => null,
            'contagion_forecast'   => null,
            'representation_index' => [],
        ];
    }
}
