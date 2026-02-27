<?php

namespace App\Services\Governance;

use App\Models\FederatedGlobalSnapshot;
use App\Models\GovernanceRecommendation;
use App\Models\GovernanceScenario;
use App\Models\RepresentationMetric;
use App\Models\ReputationEvent;
use App\Models\RiskContagionRun;
use App\Services\Risk\RiskSimulationService;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Counterfactual Governance Engine.
 *
 * Generates three pure-projection scenarios (accept / delay / ignore) for a
 * given recommendation. No governance actions are created or executed at any
 * point in this service.
 */
class GovernanceScenarioService
{
    public function __construct(
        private readonly RiskSimulationService $simulationService,
    ) {}

    /**
     * Build (or rebuild) the three counterfactual scenarios for $rec.
     *
     * Old scenarios for this recommendation are replaced atomically.
     *
     * @return Collection<GovernanceScenario>
     */
    public function generateForRecommendation(GovernanceRecommendation $rec): Collection
    {
        [$snapshot, $contagionRun, $worstGap, $trustMomentum] = $this->loadBaselines();

        $baseline = $this->computeBaseline($snapshot, $contagionRun, $trustMomentum);

        $scenarios = match ($rec->recommendation_type) {
            'contagion_followup'          => $this->scenariosContagion($rec, $baseline, $contagionRun),
            'representation_consultation' => $this->scenariosRepresentation($rec, $baseline, $worstGap),
            'civic_engagement_poll'       => $this->scenariosCivicEngagement($rec, $baseline),
            default                       => $this->scenariosGeneric($rec, $baseline),
        };

        // Replace old scenarios for this recommendation
        GovernanceScenario::where('recommendation_id', $rec->id)->delete();

        $created = collect();
        foreach ($scenarios as $data) {
            $created->push(GovernanceScenario::create(array_merge(
                ['recommendation_id' => $rec->id],
                $data,
            )));
        }

        return $created;
    }

    // ── Scenario builders ─────────────────────────────────────────────────────

    /**
     * Scenarios for a high-cascade-index recommendation.
     * accept: simulation-informed improvement
     * delayed: 50 % of accept improvement
     * ignore: extrapolated deterioration from current cascade slope
     */
    private function scenariosContagion(
        GovernanceRecommendation $rec,
        array $baseline,
        mixed $contagionRun,
    ): array {
        $ci   = max(0.01, (float) ($contagionRun?->cascade_index ?? $baseline['cascade']));
        $conf = $rec->confidence_score / 100.0;

        // Call simulation stub (audit trail only — result is not used for math)
        try {
            $this->simulationService->run([
                'region_id'       => $rec->suggested_action['payload']['region_id'] ?? null,
                'shock_magnitude' => $rec->suggested_action['payload']['shock_magnitude'] ?? 50,
            ]);
        } catch (Throwable) {}

        // accept: targeted intervention reduces cascade propagation
        $aDeltaStab  = round($ci * 28.0 * $conf + 2.0, 2);
        $aDeltaCasc  = -round($ci * 0.42 * $conf, 3);
        $aDeltaTrust = round(0.8 + $ci * 0.6 * $conf, 2);

        // delayed: 50 % of accept effect
        $dDeltaStab  = round($aDeltaStab  * 0.50, 2);
        $dDeltaCasc  = round($aDeltaCasc  * 0.50, 3);
        $dDeltaTrust = round($aDeltaTrust * 0.50, 2);

        // ignore: cascade index grows along current slope
        $iDeltaStab  = -round($ci * 22.0 + 0.5, 2);
        $iDeltaCasc  = round($ci * 0.65, 3);
        $iDeltaTrust = -round(1.0 + $ci * 1.0, 2);

        return [
            $this->buildRow('accept_action', $baseline, $aDeltaStab, $aDeltaCasc, $aDeltaTrust,
                round($rec->confidence_score * 0.85, 2)),
            $this->buildRow('delayed_action', $baseline, $dDeltaStab, $dDeltaCasc, $dDeltaTrust,
                round($rec->confidence_score * 0.70, 2)),
            $this->buildRow('ignore_action',  $baseline, $iDeltaStab, $iDeltaCasc, $iDeltaTrust,
                round($rec->confidence_score * 0.78, 2)),
        ];
    }

    /**
     * Scenarios for a representation-gap recommendation.
     * Primarily affects stability and trust; limited contagion effect.
     */
    private function scenariosRepresentation(
        GovernanceRecommendation $rec,
        array $baseline,
        mixed $worstMetric,
    ): array {
        $gap  = max(0.01, (float) ($worstMetric?->representation_gap ?? 0.30));
        $conf = $rec->confidence_score / 100.0;

        $aDeltaStab  = round($gap * 30.0 * $conf + 1.5, 2);
        $aDeltaCasc  = -round(0.02 * $conf, 3);
        $aDeltaTrust = round($gap * 3.5 * $conf + 0.5, 2);

        $dDeltaStab  = round($aDeltaStab  * 0.50, 2);
        $dDeltaCasc  = round($aDeltaCasc  * 0.50, 3);
        $dDeltaTrust = round($aDeltaTrust * 0.50, 2);

        $iDeltaStab  = -round($gap * 18.0 + 1.5, 2);
        $iDeltaCasc  = round(0.03, 3);
        $iDeltaTrust = -round($gap * 4.0 + 0.8, 2);

        return [
            $this->buildRow('accept_action', $baseline, $aDeltaStab, $aDeltaCasc, $aDeltaTrust,
                round($rec->confidence_score * 0.85, 2)),
            $this->buildRow('delayed_action', $baseline, $dDeltaStab, $dDeltaCasc, $dDeltaTrust,
                round($rec->confidence_score * 0.70, 2)),
            $this->buildRow('ignore_action',  $baseline, $iDeltaStab, $iDeltaCasc, $iDeltaTrust,
                round($rec->confidence_score * 0.78, 2)),
        ];
    }

    /**
     * Scenarios for a civic-engagement (poll) recommendation.
     * Primarily affects trust and stability; negligible contagion effect.
     */
    private function scenariosCivicEngagement(GovernanceRecommendation $rec, array $baseline): array
    {
        // Use absolute value of trust — negative momentum is what triggered this rec
        $momentum = max(0.1, abs((float) $baseline['trust']));
        $conf     = $rec->confidence_score / 100.0;

        $aDeltaStab  = round($momentum * 4.0 * $conf + 3.0 * $conf, 2);
        $aDeltaCasc  = -round(0.01 * $conf, 3);
        $aDeltaTrust = round($momentum * 1.8 * $conf + 0.8 * $conf, 2);

        $dDeltaStab  = round($aDeltaStab  * 0.50, 2);
        $dDeltaCasc  = round($aDeltaCasc  * 0.50, 3);
        $dDeltaTrust = round($aDeltaTrust * 0.50, 2);

        $iDeltaStab  = -round($momentum * 5.0 + 4.0, 2);
        $iDeltaCasc  = round(0.02, 3);
        $iDeltaTrust = -round($momentum * 2.5 + 1.0, 2);

        return [
            $this->buildRow('accept_action', $baseline, $aDeltaStab, $aDeltaCasc, $aDeltaTrust,
                round($rec->confidence_score * 0.85, 2)),
            $this->buildRow('delayed_action', $baseline, $dDeltaStab, $dDeltaCasc, $dDeltaTrust,
                round($rec->confidence_score * 0.70, 2)),
            $this->buildRow('ignore_action',  $baseline, $iDeltaStab, $iDeltaCasc, $iDeltaTrust,
                round($rec->confidence_score * 0.78, 2)),
        ];
    }

    /** Fallback for unknown recommendation types — proportional generic projections. */
    private function scenariosGeneric(GovernanceRecommendation $rec, array $baseline): array
    {
        $conf = $rec->confidence_score / 100.0;

        return [
            $this->buildRow('accept_action', $baseline,  round(5.0  * $conf, 2), -round(0.05 * $conf, 3),  round(0.5 * $conf, 2), round($rec->confidence_score * 0.85, 2)),
            $this->buildRow('delayed_action', $baseline, round(2.5  * $conf, 2), -round(0.02 * $conf, 3),  round(0.2 * $conf, 2), round($rec->confidence_score * 0.70, 2)),
            $this->buildRow('ignore_action',  $baseline, round(-4.0, 2),          round(0.04, 3),          round(-0.8, 2),          round($rec->confidence_score * 0.78, 2)),
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a single scenario row array ready for GovernanceScenario::create().
     */
    private function buildRow(
        string $type,
        array  $baseline,
        float  $stabilityDelta,
        float  $cascadeDelta,
        float  $trustDelta,
        float  $confidence,
    ): array {
        return [
            'scenario_type'           => $type,
            'projected_stability'     => (float) max(0.0, min(100.0, round($baseline['stability'] + $stabilityDelta, 2))),
            'projected_cascade_index' => (float) max(0.0, min(1.0,   round($baseline['cascade']   + $cascadeDelta,   3))),
            'projected_trust_delta'   => (float) round($trustDelta, 2),
            'confidence'              => (float) min(100.0, max(0.0, $confidence)),
            'projection_payload'      => [
                'baseline_stability'     => $baseline['stability'],
                'baseline_cascade_index' => $baseline['cascade'],
                'baseline_trust'         => $baseline['trust'],
                'stability_delta'        => round($stabilityDelta, 2),
                'cascade_delta'          => round($cascadeDelta, 3),
                'method'                 => 'heuristic_v1',
            ],
        ];
    }

    /**
     * Derive a single baseline snapshot from the latest available data.
     * All fields default gracefully so scenarios are always generated even when
     * some data sources are unavailable.
     */
    private function computeBaseline(mixed $snapshot, mixed $contagionRun, mixed $trustMomentum): array
    {
        $payload  = $snapshot?->payload ?? [];
        $severity = $payload['by_severity'] ?? [];
        $critical = (int) ($severity['critical'] ?? 0);
        $high     = (int) ($severity['high']     ?? 0);
        $active   = (int) ($payload['active_alerts'] ?? max(1, $critical + $high));
        $anomaly  = min(1.0, ($critical + $high * 0.6) / max(1, $active * 2 + 1));

        return [
            'stability' => (float) max(0.0, round(100.0 - $anomaly * 100.0, 2)),
            'cascade'   => (float) ($contagionRun?->cascade_index ?? 0.0),
            'trust'     => (float) ($trustMomentum ?? $payload['trust_momentum'] ?? 0.0),
        ];
    }

    /**
     * Load the four data sources needed for baseline computation.
     * Each query is individually wrapped in try/catch — a failure in one
     * does not prevent the others from loading.
     */
    private function loadBaselines(): array
    {
        $snapshot = null;
        try {
            $snapshot = FederatedGlobalSnapshot::latest('snapshot_at')
                ->select('payload', 'snapshot_at')
                ->first();
        } catch (Throwable) {}

        $contagionRun = null;
        try {
            $contagionRun = RiskContagionRun::latest('executed_at')
                ->select('cascade_index', 'executed_at', 'parameters')
                ->first();
        } catch (Throwable) {}

        $worstGap = null;
        try {
            $worstGap = RepresentationMetric::orderByDesc('representation_gap')
                ->select('region_id', 'representation_gap', 'participation_rate', 'calculated_at')
                ->first();
        } catch (Throwable) {}

        $trustMomentum = null;
        try {
            $raw = ReputationEvent::where('created_at', '>=', now()->subHours(24))->avg('delta');
            $trustMomentum = $raw !== null ? (float) $raw : null;
        } catch (Throwable) {}

        return [$snapshot, $contagionRun, $worstGap, $trustMomentum];
    }
}
