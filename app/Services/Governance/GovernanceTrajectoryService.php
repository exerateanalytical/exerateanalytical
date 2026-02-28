<?php

namespace App\Services\Governance;

use App\Models\FederatedGlobalSnapshot;
use App\Models\FederationRegion;
use App\Models\GovernanceTrajectory;
use App\Models\PetitionSignature;
use App\Models\PollVote;
use App\Models\RepresentationMetric;
use App\Models\ReputationEvent;
use App\Models\RiskContagionRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Governance Trajectory Engine (CT-9).
 *
 * Compares civic and governance signals between the last 24 h window and
 * the prior 24 h window to derive a direction-of-travel score for both
 * the global federation and each known region.
 *
 * Safety: read-only analytics — no governance actions are created or modified.
 * All queries are null-safe; a neutral (stable, score=0) trajectory is
 * persisted when data is insufficient.
 */
class GovernanceTrajectoryService
{
    /**
     * Compute and persist trajectory snapshots for this cycle.
     */
    public function compute(): void
    {
        $now  = Carbon::now();
        $last = [$now->copy()->subHours(24), $now->copy()];
        $prev = [$now->copy()->subHours(48), $now->copy()->subHours(24)];

        // ── Load global signals ───────────────────────────────────────────
        [$stabilityDelta, $trustDelta, $participationDelta, $contagionDelta]
            = $this->globalSignals($last, $prev);

        $globalScore = $this->score($stabilityDelta, $trustDelta, $participationDelta, $contagionDelta);

        GovernanceTrajectory::create([
            'scope'               => 'global',
            'region_id'           => null,
            'stability_delta'     => $stabilityDelta,
            'trust_delta'         => $trustDelta,
            'participation_delta' => $participationDelta,
            'contagion_delta'     => $contagionDelta,
            'trajectory_score'    => $globalScore,
            'direction'           => $this->direction($globalScore),
            'calculated_at'       => $now,
        ]);

        // ── Per-region trajectories ───────────────────────────────────────
        $regions = FederationRegion::select('id')->get();

        foreach ($regions as $region) {
            try {
                [$rStability, $rParticipation] = $this->regionSignals($region->id, $last, $prev);

                // Trust and contagion fall back to the global signal when no
                // per-region data is available (null-safe by design).
                $regionScore = $this->score(
                    $rStability,
                    $trustDelta,
                    $rParticipation,
                    $contagionDelta,
                );

                GovernanceTrajectory::create([
                    'scope'               => 'region',
                    'region_id'           => $region->id,
                    'stability_delta'     => $rStability,
                    'trust_delta'         => $trustDelta,
                    'participation_delta' => $rParticipation,
                    'contagion_delta'     => $contagionDelta,
                    'trajectory_score'    => $regionScore,
                    'direction'           => $this->direction($regionScore),
                    'calculated_at'       => $now,
                ]);
            } catch (Throwable) {
                // Skip region silently — insufficient data is an expected
                // state for newly onboarded regions.
            }
        }
    }

    // ── Score formula ─────────────────────────────────────────────────────

    private function score(
        float $stability,
        float $trust,
        float $participation,
        float $contagion,
    ): float {
        $raw = $stability * 0.35
            + $trust       * 0.30
            + $participation * 0.20
            - $contagion   * 0.15;

        return max(-100.0, min(100.0, round($raw, 3)));
    }

    private function direction(float $score): string
    {
        if ($score > 2.0)  return 'improving';
        if ($score < -2.0) return 'declining';
        return 'stable';
    }

    // ── Global signal loaders ─────────────────────────────────────────────

    /**
     * @param  array{Carbon, Carbon} $last
     * @param  array{Carbon, Carbon} $prev
     * @return array{float, float, float, float}  [stability, trust, participation, contagion]
     */
    private function globalSignals(array $last, array $prev): array
    {
        return [
            $this->stabilityDelta($last, $prev),
            $this->trustDelta($last, $prev),
            $this->participationDelta($last, $prev),
            $this->contagionDelta($last, $prev),
        ];
    }

    /**
     * Stability proxy: change in the average active_alert count from
     * FederatedGlobalSnapshot payloads.  Fewer alerts → positive delta.
     */
    private function stabilityDelta(array $last, array $prev): float
    {
        try {
            $avgLast = FederatedGlobalSnapshot::whereBetween('snapshot_at', $last)
                ->get(['payload'])
                ->avg(fn ($s) => (int) ($s->payload['active_alerts'] ?? 0));

            $avgPrev = FederatedGlobalSnapshot::whereBetween('snapshot_at', $prev)
                ->get(['payload'])
                ->avg(fn ($s) => (int) ($s->payload['active_alerts'] ?? 0));

            if ($avgLast === null || $avgPrev === null) return 0.0;

            return max(-100.0, min(100.0, round(-($avgLast - $avgPrev) * 5, 3)));
        } catch (Throwable) {
            return 0.0;
        }
    }

    /**
     * Trust: change in mean reputation_event delta between windows.
     * Scaled ×100 to bring into a useful range.
     */
    private function trustDelta(array $last, array $prev): float
    {
        try {
            $avgLast = ReputationEvent::whereBetween('created_at', $last)->avg('delta');
            $avgPrev = ReputationEvent::whereBetween('created_at', $prev)->avg('delta');

            if ($avgLast === null || $avgPrev === null) return 0.0;

            return max(-100.0, min(100.0, round(((float) $avgLast - (float) $avgPrev) * 100, 3)));
        } catch (Throwable) {
            return 0.0;
        }
    }

    /**
     * Participation: % change in combined poll-vote + petition-signature
     * volume between windows.
     */
    private function participationDelta(array $last, array $prev): float
    {
        try {
            $cntLast = PollVote::whereBetween('created_at', $last)->count()
                + PetitionSignature::whereBetween('created_at', $last)->count();

            $cntPrev = PollVote::whereBetween('created_at', $prev)->count()
                + PetitionSignature::whereBetween('created_at', $prev)->count();

            if ($cntPrev > 0) {
                $delta = round(($cntLast - $cntPrev) / $cntPrev * 100, 3);
            } elseif ($cntLast > 0) {
                $delta = 20.0; // new activity where there was none — mild positive signal
            } else {
                $delta = 0.0;
            }

            return max(-100.0, min(100.0, $delta));
        } catch (Throwable) {
            return 0.0;
        }
    }

    /**
     * Contagion: change in mean cascade_index between windows.
     * Positive = contagion worsened (subtracted in score formula).
     */
    private function contagionDelta(array $last, array $prev): float
    {
        try {
            $avgLast = RiskContagionRun::whereBetween('executed_at', $last)->avg('cascade_index');
            $avgPrev = RiskContagionRun::whereBetween('executed_at', $prev)->avg('cascade_index');

            if ($avgLast === null || $avgPrev === null) return 0.0;

            return max(-100.0, min(100.0, round(((float) $avgLast - (float) $avgPrev) * 100, 3)));
        } catch (Throwable) {
            return 0.0;
        }
    }

    // ── Per-region signal loaders ─────────────────────────────────────────

    /**
     * @return array{float, float}  [stability_delta, participation_delta]
     */
    private function regionSignals(string $regionId, array $last, array $prev): array
    {
        $stability     = $this->regionStabilityDelta($regionId, $last, $prev);
        $participation = $this->regionParticipationDelta($regionId, $last, $prev);

        return [$stability, $participation];
    }

    /**
     * Regional stability proxy: change in avg representation_gap.
     * Lower gap → better representation → positive delta.
     */
    private function regionStabilityDelta(string $regionId, array $last, array $prev): float
    {
        try {
            $gapLast = RepresentationMetric::where('region_id', $regionId)
                ->whereBetween('calculated_at', $last)
                ->avg('representation_gap');

            $gapPrev = RepresentationMetric::where('region_id', $regionId)
                ->whereBetween('calculated_at', $prev)
                ->avg('representation_gap');

            if ($gapLast === null || $gapPrev === null) return 0.0;

            return max(-100.0, min(100.0, round(-((float) $gapLast - (float) $gapPrev) * 200, 3)));
        } catch (Throwable) {
            return 0.0;
        }
    }

    /**
     * Regional participation: % change in civic activity (votes + signatures)
     * for content scoped to this region.
     */
    private function regionParticipationDelta(string $regionId, array $last, array $prev): float
    {
        try {
            $cntLast = DB::table('poll_votes')
                    ->join('polls', 'poll_votes.poll_id', '=', 'polls.id')
                    ->where('polls.region_id', $regionId)
                    ->whereBetween('poll_votes.created_at', $last)
                    ->count()
                + DB::table('petition_signatures')
                    ->join('petitions', 'petition_signatures.petition_id', '=', 'petitions.id')
                    ->where('petitions.region_id', $regionId)
                    ->whereBetween('petition_signatures.created_at', $last)
                    ->count();

            $cntPrev = DB::table('poll_votes')
                    ->join('polls', 'poll_votes.poll_id', '=', 'polls.id')
                    ->where('polls.region_id', $regionId)
                    ->whereBetween('poll_votes.created_at', $prev)
                    ->count()
                + DB::table('petition_signatures')
                    ->join('petitions', 'petition_signatures.petition_id', '=', 'petitions.id')
                    ->where('petitions.region_id', $regionId)
                    ->whereBetween('petition_signatures.created_at', $prev)
                    ->count();

            if ($cntPrev > 0) {
                $delta = round(($cntLast - $cntPrev) / $cntPrev * 100, 3);
            } elseif ($cntLast > 0) {
                $delta = 20.0;
            } else {
                $delta = 0.0;
            }

            return max(-100.0, min(100.0, $delta));
        } catch (Throwable) {
            return 0.0;
        }
    }
}
