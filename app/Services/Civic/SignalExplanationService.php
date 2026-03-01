<?php

namespace App\Services\Civic;

use App\Models\CivicSignalExplanation;
use App\Models\CivicSignalPriority;
use App\Models\GovernanceInfluence;
use App\Models\GovernanceTrajectory;
use App\Models\InstitutionalInfluence;
use Illuminate\Support\Carbon;

/**
 * CT-12 Civic Causality Engine.
 *
 * For each record in the latest CT-11 priority batch, generates an immutable
 * causal explanation by joining:
 *   - GovernanceTrajectory  → trust_delta, participation_delta, direction
 *   - GovernanceInfluence   → contagion_pressure, representation_gap scores
 *   - InstitutionalInfluence → volatility_source actor presence
 *
 * RiskProjectionSnapshot does not exist in this codebase; projected_risk_level
 * is derived from trajectory_score + top contagion influence + volatility actor
 * count, which carry equivalent signal.
 */
class SignalExplanationService
{
    public function generateForBatch(): void
    {
        $latestAt = CivicSignalPriority::max('calculated_at');
        if (! $latestAt) {
            return;
        }

        $cutoff     = Carbon::parse($latestAt)->subSeconds(90);
        $priorities = CivicSignalPriority::where('calculated_at', '>=', $cutoff)->get();

        if ($priorities->isEmpty()) {
            return;
        }

        // ── Shared context — loaded once, shared across all signals ───────────

        // Latest global governance trajectory
        $latestTrajAt = GovernanceTrajectory::where('scope', 'global')->max('calculated_at');
        $trajectory   = null;
        if ($latestTrajAt) {
            $cutoffTraj = Carbon::parse($latestTrajAt)->subSeconds(90);
            $trajectory = GovernanceTrajectory::where('scope', 'global')
                ->where('calculated_at', '>=', $cutoffTraj)
                ->latest('calculated_at')
                ->first();
        }

        // Latest governance influences (all types)
        $latestInfAt = GovernanceInfluence::max('calculated_at');
        $influences  = collect();
        if ($latestInfAt) {
            $cutoffInf = Carbon::parse($latestInfAt)->subSeconds(90);
            $influences = GovernanceInfluence::with('region:id,name,code')
                ->where('calculated_at', '>=', $cutoffInf)
                ->get();
        }

        // Latest institutional influences — volatility_source actors elevate risk
        $latestActAt    = InstitutionalInfluence::max('calculated_at');
        $volatilityCount = 0;
        if ($latestActAt) {
            $cutoffAct       = Carbon::parse($latestActAt)->subSeconds(90);
            $volatilityCount = InstitutionalInfluence::where('calculated_at', '>=', $cutoffAct)
                ->where('influence_category', 'volatility_source')
                ->where('influence_score', '>=', 40)
                ->count();
        }

        // Pre-aggregate shared influence metrics
        $topContagion = (float) ($influences->where('influence_type', 'contagion_pressure')
            ->max('influence_score') ?? 0.0);
        $topPartGap   = (float) ($influences->where('influence_type', 'participation_gap')
            ->max('influence_score') ?? 0.0);

        // Affected regions: distinct regions with any influence score > 20
        $affectedRegions = $influences
            ->filter(fn ($i) => $i->region !== null && (float) $i->influence_score > 20)
            ->groupBy('region_id')
            ->map(fn ($rows) => [
                'name'          => $rows->first()->region->name,
                'code'          => $rows->first()->region->code,
                'max_influence' => $rows->max('influence_score'),
            ])
            ->sortByDesc('max_influence')
            ->values()
            ->take(5)
            ->map(fn ($r) => ['name' => $r['name'], 'code' => $r['code']])
            ->values()
            ->toArray();

        // ── Generate an explanation for each priority ─────────────────────────
        foreach ($priorities as $priority) {
            $this->explain(
                $priority,
                $trajectory,
                $topContagion,
                $topPartGap,
                $affectedRegions,
                $volatilityCount,
            );
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function explain(
        CivicSignalPriority $priority,
        ?GovernanceTrajectory $trajectory,
        float $topContagion,
        float $topPartGap,
        array $affectedRegions,
        int   $volatilityCount,
    ): void {
        // Idempotent: skip if this priority already has an explanation.
        if (CivicSignalExplanation::where('signal_priority_id', $priority->id)->exists()) {
            return;
        }

        $velocity   = (float) $priority->participation_velocity;
        $trustDelta = $trajectory ? (float) $trajectory->trust_delta : 0.0;

        // ── Raw driver values (stored as-is; UI normalises for bars) ──────────
        $rawBreakdown = [
            'participation_velocity' => round($velocity,    3),
            'trust_delta'            => round($trustDelta,  3),
            'contagion_pressure'     => round($topContagion, 3),
            'representation_gap'     => round($topPartGap,   3),
        ];

        // ── Primary driver: normalise to 0–1 scale and pick the largest ───────
        $normalized = [
            'participation_velocity' => min(abs($velocity),    5.0) / 5.0,
            'trust_delta'            => min(abs($trustDelta),  5.0) / 5.0,
            'contagion_pressure'     => $topContagion / 100.0,
            'representation_gap'     => $topPartGap   / 100.0,
        ];
        $primaryDriver = (string) array_key_first(
            array_filter($normalized, fn ($v) => $v === max($normalized))
        );

        // ── Trajectory direction (map 'declining' → 'deteriorating') ──────────
        $trajectoryDirection = match ($trajectory?->direction ?? 'stable') {
            'improving' => 'improving',
            'declining' => 'deteriorating',
            default     => 'stable',
        };

        // ── Projected risk level ───────────────────────────────────────────────
        $trajectoryScore = $trajectory ? (float) $trajectory->trajectory_score : 0.0;
        $projectedRisk   = $this->projectRiskLevel(
            $trajectoryScore, $topContagion, $volatilityCount
        );

        // ── Concise explanation summary (≤ 240 chars) ─────────────────────────
        $summary = $this->buildSummary(
            $priority->signal_type,
            $velocity,
            $primaryDriver,
            $trajectoryDirection,
            $projectedRisk,
        );

        CivicSignalExplanation::create([
            'signal_priority_id'   => $priority->id,
            'primary_driver'       => $primaryDriver,
            'driver_breakdown'     => $rawBreakdown,
            'affected_regions'     => $affectedRegions,
            'trajectory_direction' => $trajectoryDirection,
            'projected_risk_level' => $projectedRisk,
            'explanation_summary'  => $summary,
            'created_at'           => now(),
        ]);
    }

    private function projectRiskLevel(
        float $trajectoryScore,
        float $topContagion,
        int   $volatilityCount,
    ): string {
        // Contagion influence score and volatility actors both elevate risk.
        if ($trajectoryScore <= -40 || $topContagion >= 80 || $volatilityCount >= 5) {
            return 'critical';
        }
        if ($trajectoryScore <= -15 || $topContagion >= 60 || $volatilityCount >= 3) {
            return 'high';
        }
        if ($trajectoryScore <=   5 || $topContagion >= 30) {
            return 'medium';
        }
        return 'low';
    }

    private function buildSummary(
        string $signalType,
        float  $velocity,
        string $primaryDriver,
        string $direction,
        string $riskLevel,
    ): string {
        $velLabel    = $velocity > 0.1  ? 'accelerating'
                     : ($velocity < -0.1 ? 'decelerating' : 'stable');
        $driverLabel = match ($primaryDriver) {
            'participation_velocity' => 'participation momentum',
            'trust_delta'            => 'trust dynamics',
            'contagion_pressure'     => 'risk contagion pressure',
            'representation_gap'     => 'representation gap',
            default                  => str_replace('_', ' ', $primaryDriver),
        };
        $dirLabel = match ($direction) {
            'improving'     => 'improving',
            'deteriorating' => 'deteriorating',
            default         => 'stable',
        };

        $text = ucfirst($signalType) . " engagement is {$velLabel}. "
            . "Primary driver: {$driverLabel}. "
            . "Governance trajectory is {$dirLabel} "
            . "with {$riskLevel} projected risk.";

        return mb_substr($text, 0, 240);
    }
}
