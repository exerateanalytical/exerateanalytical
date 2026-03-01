<?php

namespace App\Services\Governance;

use App\Models\FederatedGlobalSnapshot;
use App\Models\GovernanceAction;
use App\Models\GovernanceActionOutcome;
use App\Models\GovernanceTrajectory;
use App\Models\RepresentationMetric;
use App\Models\RiskContagionRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CT-14 Governance Memory Engine.
 *
 * Measures real-world outcomes of executed governance actions by comparing
 * the governance system state at execution time against state at each
 * observation window (24 h, 7 d, 30 d).
 *
 * Primary sources (ordered by specificity):
 *  - GovernanceTrajectory  → stability, trust, participation, contagion deltas
 *  - RiskContagionRun      → cascade_index absolute values → cascade delta
 *  - RepresentationMetric  → participation_rate cross-reference
 *  - FederatedGlobalSnapshot → global payload baseline cross-check
 */
class GovernanceOutcomeService
{
    /** Observation window definitions: label → elapsed hours. */
    private const WINDOW_HOURS = [
        '24h' => 24,
        '7d'  => 168,
        '30d' => 720,
    ];

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Measure all eligible windows for the given action.
     *
     * Idempotent: skips any window that already has a row and any window
     * whose elapsed time has not yet passed.
     *
     * @return Collection<GovernanceActionOutcome>
     */
    public function measureOutcomes(GovernanceAction $action): Collection
    {
        if (! $action->executed_at) {
            return collect();
        }

        $results = collect();

        foreach (self::WINDOW_HOURS as $window => $hours) {
            $targetAt = $action->executed_at->copy()->addHours($hours);

            // Window has not elapsed yet — skip silently
            if ($targetAt->isFuture()) {
                continue;
            }

            // Already measured for this window — idempotent guard
            if (GovernanceActionOutcome::where('governance_action_id', $action->id)
                ->where('observation_window', $window)
                ->exists()) {
                continue;
            }

            $outcome = $this->computeWindow($action, $window, $targetAt);

            if ($outcome) {
                $results->push($outcome);
            }
        }

        return $results;
    }

    /**
     * Future hook for CT-13 ranking weight calibration.
     *
     * Returns the mean effectiveness_score across all measured outcomes
     * for actions of the given type.  Returns 0.0 when no data is available.
     */
    public function averageEffectivenessForActionType(string $actionType): float
    {
        $avg = DB::table('governance_action_outcomes as o')
            ->join('governance_actions as a', 'a.id', '=', 'o.governance_action_id')
            ->where('a.action_type', $actionType)
            ->avg('o.effectiveness_score');

        return round((float) ($avg ?? 0.0), 4);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Compute and persist a single outcome row for the given window.
     */
    private function computeWindow(
        GovernanceAction $action,
        string $window,
        Carbon $targetAt,
    ): ?GovernanceActionOutcome {
        // ── 1. Baseline snapshot ──────────────────────────────────────────────
        //
        // Prefer values stored directly in result_snapshot (set at execution
        // time by GovernanceActionService). Fall back to querying the closest
        // historical row from each source table when the key is absent.
        $snap = $action->result_snapshot ?? [];

        // ── 2. GovernanceTrajectory: sum deltas between execution and target ──
        //
        // Summing period-over-period deltas gives us the net change across
        // the full observation window for stability, trust, participation,
        // and contagion.
        $trajRow = GovernanceTrajectory::where('scope', 'global')
            ->whereBetween('calculated_at', [
                $action->executed_at,
                $targetAt,
            ])
            ->selectRaw('
                COALESCE(SUM(stability_delta),     0) AS stability_sum,
                COALESCE(SUM(trust_delta),         0) AS trust_sum,
                COALESCE(SUM(participation_delta), 0) AS participation_sum,
                COALESCE(SUM(contagion_delta),     0) AS contagion_sum,
                MAX(calculated_at)                    AS last_calculated_at
            ')
            ->first();

        $stabilityDelta     = round((float) ($trajRow->stability_sum     ?? 0), 2);
        $trustDelta         = round((float) ($trajRow->trust_sum         ?? 0), 2);
        $contagionTrajDelta = round((float) ($trajRow->contagion_sum     ?? 0), 3);

        // ── 3. RiskContagionRun: cascade_index delta ──────────────────────────
        //
        // Use the closest run at execution time as baseline, closest run at
        // targetAt as the window measurement, then compute the signed delta.
        $cascadeBaseline = (float) (
            $snap['baseline_cascade_index']
            ?? $snap['cascade_index']
            ?? RiskContagionRun::where('executed_at', '<=', $action->executed_at)
                ->orderByDesc('executed_at')
                ->value('cascade_index')
            ?? 0.0
        );

        $cascadeAtWindow = (float) (
            RiskContagionRun::where('executed_at', '<=', $targetAt)
                ->orderByDesc('executed_at')
                ->value('cascade_index')
            ?? $cascadeBaseline
        );

        $cascadeDelta = round($cascadeAtWindow - $cascadeBaseline, 3);

        // ── 4. RepresentationMetric: participation_rate delta ─────────────────
        //
        // Average participation_rate across all regions at execution time vs
        // target time.  Falls back to trajectory participation sum if no
        // metric rows exist.
        $participationDelta = $this->participationDelta(
            $snap,
            $action->executed_at,
            $targetAt,
            $trajRow->participation_sum ?? 0,
        );

        // ── 5. Effectiveness score ────────────────────────────────────────────
        $raw = ($stabilityDelta     * 0.4)
             + ($trustDelta         * 0.3)
             + ($participationDelta * 0.2)
             - ($cascadeDelta       * 0.1);

        $effectivenessScore = round(max(-100.0, min(100.0, $raw)), 2);

        // ── 6. Measurement timestamp ──────────────────────────────────────────
        $measuredAt = $trajRow->last_calculated_at
            ? Carbon::parse($trajRow->last_calculated_at)
            : $targetAt;

        return GovernanceActionOutcome::create([
            'governance_action_id'  => $action->id,
            'observation_window'    => $window,
            'stability_delta'       => $stabilityDelta,
            'trust_delta'           => $trustDelta,
            'participation_delta'   => $participationDelta,
            'cascade_delta'         => $cascadeDelta,
            'effectiveness_score'   => $effectivenessScore,
            'measured_at'           => $measuredAt,
        ]);
    }

    /**
     * Derive participation_delta from RepresentationMetric rows (avg across
     * all regions at two time points), falling back to the trajectory sum.
     */
    private function participationDelta(
        array $snap,
        Carbon $executedAt,
        Carbon $targetAt,
        float $trajFallback,
    ): float {
        // Try result_snapshot first
        if (isset($snap['baseline_participation']) || isset($snap['participation_rate'])) {
            $base = (float) ($snap['baseline_participation'] ?? $snap['participation_rate']);

            $latestAt = RepresentationMetric::where('calculated_at', '<=', $targetAt)
                ->max('calculated_at');

            if ($latestAt) {
                $current = (float) RepresentationMetric::where('calculated_at', $latestAt)
                    ->avg('participation_rate');
                return round($current - $base, 2);
            }
        }

        // Query two time-point averages from RepresentationMetric
        $latestAtExec = RepresentationMetric::where('calculated_at', '<=', $executedAt)
            ->max('calculated_at');

        $latestAtWindow = RepresentationMetric::where('calculated_at', '<=', $targetAt)
            ->max('calculated_at');

        if ($latestAtExec && $latestAtWindow) {
            $baseRate = (float) RepresentationMetric::where('calculated_at', $latestAtExec)
                ->avg('participation_rate');
            $windowRate = (float) RepresentationMetric::where('calculated_at', $latestAtWindow)
                ->avg('participation_rate');

            return round($windowRate - $baseRate, 2);
        }

        // No representation data — fall back to trajectory participation sum
        return round((float) $trajFallback, 2);
    }
}
