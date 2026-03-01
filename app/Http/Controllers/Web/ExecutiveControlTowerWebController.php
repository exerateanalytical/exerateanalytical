<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CivicSignalPriority;
use App\Models\GovernanceAssignment;
use App\Models\GovernanceInfluence;
use App\Models\GovernanceRecommendation;
use App\Models\GovernanceTrajectory;
use App\Models\InstitutionalInfluence;
use App\Services\Civic\SignalPriorityService;
use App\Services\Executive\ControlTowerService;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ExecutiveControlTowerWebController extends Controller
{
    /**
     * GET /executive/control-tower
     *
     * Renders the Control Tower dashboard, seeding data via Inertia props from
     * the same ControlTowerService used by the API endpoint. The Vue page can
     * refresh live via GET /api/v1/executive/control-tower.
     */
    public function index(ControlTowerService $service, SignalPriorityService $priorityService): Response
    {
        $payload = $service->build();

        // Seed active recommendations ordered critical → low.
        $recommendations = GovernanceRecommendation::where('status', 'active')
            ->orderByRaw(GovernanceRecommendation::severityWeight() . ' DESC')
            ->orderByDesc('confidence_score')
            ->get();

        // Seed latest influence signals (most recent batch, grouped by type then score).
        $latestInfluenceAt = GovernanceInfluence::max('calculated_at');
        $influences = collect();
        if ($latestInfluenceAt) {
            $cutoff = Carbon::parse($latestInfluenceAt)->subSeconds(60);
            $influences = GovernanceInfluence::with('region:id,code,name')
                ->where('calculated_at', '>=', $cutoff)
                ->orderByRaw("CASE influence_type
                    WHEN 'contagion_pressure' THEN 1
                    WHEN 'trust_shift'        THEN 2
                    WHEN 'participation_gap'  THEN 3
                    ELSE 4 END ASC")
                ->orderByDesc('influence_score')
                ->get();
        }

        // Seed latest institutional actor influence snapshot (executive-only).
        $latestActorAt = InstitutionalInfluence::max('calculated_at');
        $actors = collect();
        if ($latestActorAt) {
            $cutoff = Carbon::parse($latestActorAt)->subSeconds(60);
            $actors = InstitutionalInfluence::with('actor:id,name,email', 'region:id,code,name')
                ->where('calculated_at', '>=', $cutoff)
                ->orderByDesc('influence_score')
                ->get();
        }

        // Seed latest governance trajectory + 7-day sparkline data.
        $latestTrajectoryAt = GovernanceTrajectory::where('scope', 'global')->max('calculated_at');
        $trajectoryGlobal   = null;
        $trajectoryRegions  = collect();
        $trajectoryTrend    = collect();
        if ($latestTrajectoryAt) {
            $cutoff = Carbon::parse($latestTrajectoryAt)->subSeconds(90);

            $trajectoryGlobal = GovernanceTrajectory::where('scope', 'global')
                ->where('calculated_at', '>=', $cutoff)
                ->latest('calculated_at')
                ->first();

            $trajectoryRegions = GovernanceTrajectory::with('region:id,code,name')
                ->where('scope', 'region')
                ->where('calculated_at', '>=', $cutoff)
                ->orderByDesc('trajectory_score')
                ->get();

            $trajectoryTrend = GovernanceTrajectory::where('scope', 'global')
                ->where('calculated_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('calculated_at')
                ->get(['trajectory_score', 'direction', 'calculated_at'])
                ->groupBy(fn ($r) => Carbon::parse($r->calculated_at)->format('Y-m-d'))
                ->map(fn ($rows) => $rows->last())
                ->values();
        }

        // ── CT-11 Civic Signal Priorities ─────────────────────────────────────
        $civicPriorities  = collect();
        $latestPriorityAt = CivicSignalPriority::max('calculated_at');
        if ($latestPriorityAt) {
            $cutoff          = Carbon::parse($latestPriorityAt)->subSeconds(90);
            $civicPriorities = CivicSignalPriority::where('calculated_at', '>=', $cutoff)
                ->orderByDesc('priority_score')
                ->limit(20)
                ->get();
            $priorityService->hydrateSignals($civicPriorities);
        }

        // ── CT-15 Institutional Coordination — current assignments ────────────
        $assignments = GovernanceAssignment::with([
            'action:id,action_type',
            'assignedBy:id,name',
        ])
        ->orderByRaw("CASE status
            WHEN 'blocked'      THEN 1
            WHEN 'in_progress'  THEN 2
            WHEN 'assigned'     THEN 3
            WHEN 'acknowledged' THEN 4
            WHEN 'completed'    THEN 5
            ELSE 6 END")
        ->orderByDesc('created_at')
        ->limit(50)
        ->get();

        return Inertia::render('Executive/ControlTower', [
            'hero'                 => $payload['hero'],
            'regions'              => $payload['regions'],
            'policy_radar'         => $payload['policy_radar'],
            'civic_momentum'       => $payload['civic_momentum'],
            'contagion_forecast'   => $payload['contagion_forecast'],
            'representation_index' => $payload['representation_index'],
            'recommendations'      => $recommendations,
            'influences'           => $influences,
            'actors'               => $actors,
            'trajectory'           => [
                'global'  => $trajectoryGlobal,
                'regions' => $trajectoryRegions,
                'trend'   => $trajectoryTrend,
            ],
            'civic_priorities'     => $civicPriorities,
            'assignments'          => $assignments,
            'generated_at'         => now()->toIso8601String(),
        ]);
    }
}
