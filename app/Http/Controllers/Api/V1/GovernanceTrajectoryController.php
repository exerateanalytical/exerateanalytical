<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GovernanceTrajectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class GovernanceTrajectoryController extends Controller
{
    /**
     * GET /api/v1/executive/trajectory
     *
     * Returns the latest global trajectory, latest per-region trajectories,
     * and the last 7 days of global snapshots (one per day) for sparkline
     * rendering.
     *
     * Public read endpoint — data is aggregated, non-sensitive, and used
     * by the executive Control Tower dashboard refresh cycle.
     */
    public function index(): JsonResponse
    {
        $latestAt = GovernanceTrajectory::where('scope', 'global')->max('calculated_at');

        // Neutral trajectory returned when no computation has run yet.
        if (! $latestAt) {
            return response()->json([
                'global'  => null,
                'regions' => [],
                'trend'   => [],
            ]);
        }

        $cutoff = Carbon::parse($latestAt)->subSeconds(90);

        // Latest global snapshot
        $global = GovernanceTrajectory::where('scope', 'global')
            ->where('calculated_at', '>=', $cutoff)
            ->latest('calculated_at')
            ->first();

        // Latest per-region snapshots (correlated to the same computation cycle)
        $regions = GovernanceTrajectory::with('region:id,code,name')
            ->where('scope', 'region')
            ->where('calculated_at', '>=', $cutoff)
            ->orderByDesc('trajectory_score')
            ->get();

        // 7-day sparkline: one representative sample per calendar day
        $trend = GovernanceTrajectory::where('scope', 'global')
            ->where('calculated_at', '>=', Carbon::now()->subDays(7))
            ->orderBy('calculated_at')
            ->get(['trajectory_score', 'direction', 'calculated_at'])
            ->groupBy(fn ($r) => Carbon::parse($r->calculated_at)->format('Y-m-d'))
            ->map(fn ($rows) => $rows->last())   // latest sample of each day
            ->values();

        return response()->json([
            'global'  => $global,
            'regions' => $regions,
            'trend'   => $trend,
        ]);
    }
}
