<?php

namespace App\Services\Governance;

use App\Models\GovernanceDecisionRanking;
use App\Models\GovernanceRecommendation;
use App\Models\GovernanceScenario;
use Illuminate\Support\Collection;

/**
 * CT-13 Governance Decision Ranking Engine.
 *
 * Scores and ranks counterfactual governance scenarios for a recommendation
 * using a weighted multi-criteria formula. Rankings are immutable per cycle —
 * existing rows are replaced atomically on each run.
 */
class DecisionRankingService
{
    /**
     * Score and rank all scenarios for the given recommendation.
     *
     * Formula:
     *   decision_score = (stability_delta × 0.4)
     *                  + (trust_delta      × 0.3)
     *                  − (cascade_delta    × 0.2)
     *                  + (participation_delta × 0.1)
     *   Clamped to [−100, +100].
     *
     * @return Collection<GovernanceDecisionRanking>
     */
    public function rankForRecommendation(GovernanceRecommendation $rec): Collection
    {
        $scenarios = GovernanceScenario::where('recommendation_id', $rec->id)->get();

        if ($scenarios->isEmpty()) {
            return collect();
        }

        // Score each scenario
        $scored = $scenarios->map(function (GovernanceScenario $scenario) {
            $stabilityDelta     = (float) ($scenario->projection_payload['stability_delta']  ?? 0.0);
            $trustDelta         = (float) ($scenario->projected_trust_delta                  ?? 0.0);
            $cascadeDelta       = (float) ($scenario->projection_payload['cascade_delta']    ?? 0.0);
            $participationDelta = 0.0; // Not projected in governance scenarios

            $raw = ($stabilityDelta    * 0.4)
                 + ($trustDelta        * 0.3)
                 - ($cascadeDelta      * 0.2)
                 + ($participationDelta * 0.1);

            $score = max(-100.0, min(100.0, round($raw, 2)));

            return [
                'scenario'               => $scenario,
                'stability_delta'        => $stabilityDelta,
                'trust_delta'            => $trustDelta,
                'cascade_delta'          => $cascadeDelta,
                'participation_delta'    => $participationDelta,
                'decision_score'         => $score,
            ];
        });

        // Sort descending by score, then assign rank_position
        $ranked = $scored->sortByDesc('decision_score')->values();

        // Replace old rankings atomically
        GovernanceDecisionRanking::where('recommendation_id', $rec->id)->delete();

        $created = collect();
        foreach ($ranked as $position => $row) {
            $created->push(GovernanceDecisionRanking::create([
                'recommendation_id'             => $rec->id,
                'scenario_type'                 => $row['scenario']->scenario_type,
                'projected_stability_delta'     => $row['stability_delta'],
                'projected_trust_delta'         => $row['trust_delta'],
                'projected_cascade_delta'       => $row['cascade_delta'],
                'projected_participation_delta' => $row['participation_delta'],
                'decision_score'                => $row['decision_score'],
                'rank_position'                 => $position + 1,
            ]));
        }

        return $created;
    }
}
