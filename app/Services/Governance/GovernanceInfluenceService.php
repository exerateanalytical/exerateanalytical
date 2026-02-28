<?php

namespace App\Services\Governance;

use App\Models\GovernanceInfluence;
use App\Models\Petition;
use App\Models\Poll;
use App\Models\RepresentationMetric;
use App\Models\ReputationEvent;
use App\Models\RiskContagionRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Governance Causality & Influence Service.
 *
 * Computes read-only influence signals from civic, risk, and trust data sources
 * and persists them as immutable time-series snapshots.
 *
 * Safety: this service never creates or modifies governance actions.
 */
class GovernanceInfluenceService
{
    // Score threshold below which we skip inserting a signal (noise filter)
    private const MIN_SCORE = 0.5;

    /**
     * Compute influence signals for the current cycle and persist them.
     *
     * Old rows beyond a 2-hour retention window are pruned so the table stays lean.
     *
     * @return Collection<GovernanceInfluence>
     */
    public function compute(): Collection
    {
        $now = Carbon::now();

        [$repMetrics, $contagionRun, $pollCounts, $petitionCounts, $trustMomentum]
            = $this->loadData($now);

        $signals = collect();

        // ── Signal A: participation_gap per region (from RepresentationMetric) ──
        // Each region with measurable representation data gets a signal.
        // High gap (> 0.2) = risk rising (up); healthy gap = risk stabilising (down).
        foreach ($repMetrics as $metric) {
            try {
                $gap = (float) $metric->representation_gap;
                if ($gap < 0.0) continue;

                $score = min(100.0, round($gap * 100.0, 3));
                if ($score < self::MIN_SCORE) continue;

                $signals->push([
                    'region_id'        => $metric->region_id,
                    'source_type'      => 'trust',
                    'source_id'        => $metric->id,
                    'influence_type'   => 'participation_gap',
                    'influence_score'  => $score,
                    'impact_direction' => $gap > 0.20 ? 'up' : 'down',
                    'calculated_at'    => $now,
                ]);
            } catch (Throwable) {}
        }

        // ── Signal B: contagion_pressure (from RiskContagionRun) ─────────────
        // The latest contagion run's cascade index, attributed to its origin region.
        if ($contagionRun !== null) {
            try {
                $ci       = (float) $contagionRun->cascade_index;
                $score    = min(100.0, round($ci * 100.0, 3));
                $regionId = $contagionRun->parameters['region_id'] ?? null;

                if ($score >= self::MIN_SCORE) {
                    $signals->push([
                        'region_id'        => $regionId,
                        'source_type'      => 'risk',
                        'source_id'        => $contagionRun->id,
                        'influence_type'   => 'contagion_pressure',
                        'influence_score'  => $score,
                        'impact_direction' => $ci > 0.35 ? 'up' : 'down',
                        'calculated_at'    => $now,
                    ]);
                }
            } catch (Throwable) {}
        }

        // ── Signal C: trust_shift (global, from ReputationEvent momentum) ────
        // Negative trust momentum = erosion = risk rising; positive = recovery.
        if ($trustMomentum !== null) {
            try {
                $score = min(100.0, round(abs($trustMomentum) * 50.0 + 5.0, 3));

                if ($score >= self::MIN_SCORE) {
                    $signals->push([
                        'region_id'        => null,
                        'source_type'      => 'trust',
                        'source_id'        => null,
                        'influence_type'   => 'trust_shift',
                        'influence_score'  => $score,
                        'impact_direction' => $trustMomentum < 0 ? 'up' : 'down',
                        'calculated_at'    => $now,
                    ]);
                }
            } catch (Throwable) {}
        }

        // ── Signal D: poll + petition engagement per region ───────────────────
        // High recent activity = civic participation increasing = risk decreasing.
        // Low activity in a region with known gap = widening participation issue.
        try {
            $allRegionIds = $pollCounts->keys()->merge($petitionCounts->keys())->unique();

            foreach ($allRegionIds as $regionId) {
                $pollCount     = (int) ($pollCounts[$regionId]     ?? 0);
                $petitionCount = (int) ($petitionCounts[$regionId] ?? 0);
                $total         = $pollCount + $petitionCount;

                if ($total === 0) continue;

                // Source type reflects the dominant signal
                $sourceType = $pollCount >= $petitionCount ? 'poll' : 'petition';
                $score      = min(100.0, round($total * 8.0, 3));

                if ($score < self::MIN_SCORE) continue;

                $signals->push([
                    'region_id'        => $regionId,
                    'source_type'      => $sourceType,
                    'source_id'        => null,
                    'influence_type'   => 'participation_gap',
                    'influence_score'  => $score,
                    // Active engagement closes the participation gap → risk down
                    'impact_direction' => $total >= 3 ? 'down' : 'up',
                    'calculated_at'    => $now,
                ]);
            }
        } catch (Throwable) {}

        // ── Persist: prune stale rows then insert the current batch ───────────
        GovernanceInfluence::where('calculated_at', '<', $now->copy()->subHours(2))->delete();

        foreach ($signals as $signal) {
            try {
                GovernanceInfluence::create($signal);
            } catch (Throwable) {}
        }

        // Return this cycle's rows (within 1 minute of $now for race-condition safety)
        return GovernanceInfluence::with('region:id,code,name')
            ->where('calculated_at', '>=', $now->copy()->subMinute())
            ->orderByDesc('influence_score')
            ->get();
    }

    // ── Data loaders ──────────────────────────────────────────────────────────

    private function loadData(Carbon $now): array
    {
        // Latest representation metric per region via correlated subquery
        $repMetrics = collect();
        try {
            $repMetrics = RepresentationMetric::whereRaw(
                'calculated_at = (SELECT MAX(s2.calculated_at) FROM representation_metrics s2
                                  WHERE s2.region_id = representation_metrics.region_id)'
            )->select('id', 'region_id', 'representation_gap', 'participation_rate', 'calculated_at')
             ->get();
        } catch (Throwable) {}

        // Latest contagion run
        $contagionRun = null;
        try {
            $contagionRun = RiskContagionRun::latest('executed_at')
                ->select('id', 'cascade_index', 'executed_at', 'parameters')
                ->first();
        } catch (Throwable) {}

        // Poll count per region — last 24 h
        $pollCounts = collect();
        try {
            $pollCounts = Poll::selectRaw('region_id, count(*) as cnt')
                ->whereNotNull('region_id')
                ->where('created_at', '>=', $now->copy()->subHours(24))
                ->groupBy('region_id')
                ->pluck('cnt', 'region_id');
        } catch (Throwable) {}

        // Petition count per region — last 24 h
        $petitionCounts = collect();
        try {
            $petitionCounts = Petition::selectRaw('region_id, count(*) as cnt')
                ->whereNotNull('region_id')
                ->where('created_at', '>=', $now->copy()->subHours(24))
                ->groupBy('region_id')
                ->pluck('cnt', 'region_id');
        } catch (Throwable) {}

        // Global trust momentum — avg reputation delta last 24 h
        $trustMomentum = null;
        try {
            $raw = ReputationEvent::where('created_at', '>=', $now->copy()->subHours(24))
                ->avg('delta');
            $trustMomentum = $raw !== null ? (float) $raw : null;
        } catch (Throwable) {}

        return [$repMetrics, $contagionRun, $pollCounts, $petitionCounts, $trustMomentum];
    }
}
