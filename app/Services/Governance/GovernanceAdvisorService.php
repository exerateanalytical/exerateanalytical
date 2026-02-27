<?php

namespace App\Services\Governance;

use App\Models\FederatedGlobalSnapshot;
use App\Models\GovernanceRecommendation;
use App\Models\Petition;
use App\Models\Poll;
use App\Models\RegionalSystemicSnapshot;
use App\Models\RepresentationMetric;
use App\Models\ReputationEvent;
use App\Models\RiskContagionRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class GovernanceAdvisorService
{
    // Recommendation TTL: 30 minutes (scheduler runs every 10 min, so ~3 cycles)
    private const EXPIRE_MINUTES = 30;

    /**
     * Generate fresh governance recommendations, replacing any currently active set.
     *
     * Safety guarantee: this method NEVER executes any governance action.
     * It only creates recommendation records. Human approval is mandatory for
     * any action to be taken.
     *
     * @return Collection<GovernanceRecommendation>
     */
    public function generateRecommendations(): Collection
    {
        [$contagionRun, $globalSnapshot, $worstRepresentation, $trustMomentum, $civicTrend]
            = $this->readInputs();

        $recommendations = collect();

        // ── Rule 1: Contagion follow-up ───────────────────────────────────────
        // IF the latest cascade_index > 0.35 → investigate systemic propagation
        try {
            if ($contagionRun && $contagionRun->cascade_index > 0.35) {
                $ci         = (float) $contagionRun->cascade_index;
                $severity   = $ci > 0.65 ? 'critical' : ($ci > 0.50 ? 'high' : 'medium');
                $confidence = (float) min(95.00, round($ci * 200, 2));
                $regionId   = $contagionRun->parameters['region_id'] ?? null;

                $recommendations->push([
                    'recommendation_type' => 'contagion_followup',
                    'severity'            => $severity,
                    'title'               => 'Elevated Cascade Index Detected — Contagion Simulation Recommended',
                    'rationale'           => sprintf(
                        'The latest risk contagion run recorded a cascade index of %.3f, which exceeds the '
                        . 'safe threshold of 0.35. A targeted contagion simulation can help quantify the '
                        . 'propagation envelope and identify which regions are most exposed. Immediate '
                        . 'modelling is advised to inform containment decisions.',
                        $ci
                    ),
                    'suggested_action'    => [
                        'action_type' => 'simulate_contagion',
                        'payload'     => array_filter([
                            'region_id'        => $regionId,
                            'shock_magnitude'  => (int) min(80, round($ci * 100)),
                            'contagion_factor' => (float) min(0.70, round($ci, 2)),
                            'iterations'       => 10,
                        ]),
                    ],
                    'confidence_score'    => $confidence,
                    'source_snapshot_at'  => $contagionRun->executed_at,
                ]);
            }
        } catch (Throwable) {}

        // ── Rule 2: Representation gap → open consultation ───────────────────
        // IF any region's representation_gap > 0.30 → launch policy consultation
        try {
            if ($worstRepresentation && $worstRepresentation->representation_gap > 0.30) {
                $gap        = (float) $worstRepresentation->representation_gap;
                $severity   = $gap > 0.55 ? 'critical' : ($gap > 0.40 ? 'high' : 'medium');
                $confidence = (float) min(90.00, round($gap * 250, 2));
                $regionId   = $worstRepresentation->region_id;

                $title    = 'Address Representation Deficit in Region ' . ($regionId ? substr($regionId, 0, 8) : 'N/A');
                $abstract = sprintf(
                    'Representation gap of %.1f%% detected in the most under-represented region. '
                    . 'A structured consultation can surface barriers to participation and co-develop '
                    . 'targeted inclusion policies.',
                    $gap * 100
                );

                $recommendations->push([
                    'recommendation_type' => 'representation_consultation',
                    'severity'            => $severity,
                    'title'               => $title,
                    'rationale'           => sprintf(
                        'Region %s records a representation gap of %.1f%% against a safe ceiling of 30%%. '
                        . 'Participation rate stands at %.1f%%. This structural deficit risks delegitimising '
                        . 'governance outcomes and eroding institutional trust over time. Opening a policy '
                        . 'consultation is the lowest-risk first step.',
                        $regionId ? substr($regionId, 0, 8) . '…' : 'unknown',
                        $gap * 100,
                        ((float) $worstRepresentation->participation_rate) * 100
                    ),
                    'suggested_action'    => [
                        'action_type' => 'open_consultation',
                        'payload'     => [
                            'title'     => $title,
                            'abstract'  => $abstract,
                            'full_text' => $abstract,
                        ],
                    ],
                    'confidence_score'    => $confidence,
                    'source_snapshot_at'  => $worstRepresentation->calculated_at ?? null,
                ]);
            }
        } catch (Throwable) {}

        // ── Rule 3: Negative trust + rising civic activity → launch poll ─────
        // IF trust_momentum < 0 AND civic activity is growing → channel momentum
        try {
            if ($trustMomentum !== null && $trustMomentum < 0 && ($civicTrend['rising'] ?? false)) {
                $severity   = $trustMomentum < -1.0 ? 'high' : 'medium';
                $confidence = (float) min(85.00, round(65.00 + abs($trustMomentum) * 10, 2));

                $recommendations->push([
                    'recommendation_type' => 'civic_engagement_poll',
                    'severity'            => $severity,
                    'title'               => 'Trust Erosion with Rising Civic Activity — Launch Engagement Poll',
                    'rationale'           => sprintf(
                        'Trust momentum is negative (%.2f Δ/day) while civic activity is rising '
                        . '(%d participations vs %d in the prior period). This divergence — citizens '
                        . 'engaging more while trust falls — indicates frustration-driven participation. '
                        . 'A timely engagement poll can surface grievances before they escalate and '
                        . 'demonstrate responsive governance.',
                        $trustMomentum,
                        $civicTrend['current_24h'] ?? 0,
                        $civicTrend['prev_24h'] ?? 0
                    ),
                    'suggested_action'    => [
                        'action_type' => 'launch_poll',
                        'payload'     => [
                            'title'      => 'How satisfied are you with current government responsiveness?',
                            'options'    => [
                                'Very satisfied',
                                'Somewhat satisfied',
                                'Neutral',
                                'Somewhat dissatisfied',
                                'Very dissatisfied',
                            ],
                            'visibility' => 'public',
                            'type'       => 'single_choice',
                            'status'     => 'active',
                        ],
                    ],
                    'confidence_score'    => $confidence,
                    'source_snapshot_at'  => now(),
                ]);
            }
        } catch (Throwable) {}

        // ── Persist: expire stale active recs, insert fresh ones ─────────────
        return DB::transaction(function () use ($recommendations) {
            GovernanceRecommendation::where('status', 'active')->update(['status' => 'expired']);

            $expiresAt = now()->addMinutes(self::EXPIRE_MINUTES);
            $created   = collect();

            foreach ($recommendations as $data) {
                $rec = GovernanceRecommendation::create(array_merge($data, [
                    'status'     => 'active',
                    'expires_at' => $expiresAt,
                ]));
                $created->push($rec);
            }

            return $created;
        });
    }

    // ── Input readers ─────────────────────────────────────────────────────────

    private function readInputs(): array
    {
        // Q1: Latest contagion run
        $contagionRun = null;
        try {
            $contagionRun = RiskContagionRun::latest('executed_at')
                ->select('cascade_index', 'executed_at', 'parameters')
                ->first();
        } catch (Throwable) {}

        // Q2: Global snapshot (for context)
        $globalSnapshot = null;
        try {
            $globalSnapshot = FederatedGlobalSnapshot::latest('snapshot_at')
                ->select('payload', 'snapshot_at')
                ->first();
        } catch (Throwable) {}

        // Q3: Worst representation metric (highest gap)
        $worstRepresentation = null;
        try {
            $worstRepresentation = RepresentationMetric::orderByDesc('representation_gap')
                ->select('region_id', 'representation_gap', 'participation_rate', 'calculated_at')
                ->first();
        } catch (Throwable) {}

        // Q4: Trust momentum — avg delta of reputation events in last 24 h
        $trustMomentum = null;
        try {
            $trustMomentum = ReputationEvent::where('created_at', '>=', now()->subHours(24))
                ->avg('delta');
            $trustMomentum = $trustMomentum !== null ? (float) $trustMomentum : null;
        } catch (Throwable) {}

        // Q5: Civic activity trend — compare last 24 h vs prior 24 h
        $civicTrend = ['rising' => false, 'current_24h' => 0, 'prev_24h' => 0];
        try {
            $now        = now();
            $cutoff24h  = $now->copy()->subHours(24);
            $cutoff48h  = $now->copy()->subHours(48);

            $current = (Poll::where('created_at', '>=', $cutoff24h)->count())
                     + (Petition::where('created_at', '>=', $cutoff24h)->count());

            $prev    = (Poll::whereBetween('created_at', [$cutoff48h, $cutoff24h])->count())
                     + (Petition::whereBetween('created_at', [$cutoff48h, $cutoff24h])->count());

            $civicTrend = [
                'rising'       => $current > $prev,
                'current_24h'  => $current,
                'prev_24h'     => $prev,
            ];
        } catch (Throwable) {}

        return [$contagionRun, $globalSnapshot, $worstRepresentation, $trustMomentum, $civicTrend];
    }
}
