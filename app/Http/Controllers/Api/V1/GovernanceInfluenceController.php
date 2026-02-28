<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GovernanceInfluence;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GovernanceInfluenceController extends Controller
{
    /**
     * GET /api/v1/executive/influences
     *
     * Returns the most recent cycle of influence signals, grouped by region and
     * ordered by influence_score DESC within each group.
     *
     * "Most recent cycle" = rows written in the last 11 minutes (one everyTenMinutes
     * window with 1-minute tolerance). Falls back to the last 2 hours if the
     * scheduler hasn't run recently.
     *
     * No authentication required — read-only analytics.
     */
    public function index(): JsonResponse
    {
        // Find the timestamp of the latest batch
        $latestAt = GovernanceInfluence::max('calculated_at');

        if (! $latestAt) {
            return response()->json(['data' => [], 'grouped' => []]);
        }

        // Fetch all rows from the same batch (within 60 s of the latest timestamp)
        $cutoff = Carbon::parse($latestAt)->subSeconds(60);

        $influences = GovernanceInfluence::with('region:id,code,name')
            ->where('calculated_at', '>=', $cutoff)
            ->orderByRaw("
                CASE influence_type
                    WHEN 'contagion_pressure' THEN 1
                    WHEN 'trust_shift'        THEN 2
                    WHEN 'participation_gap'  THEN 3
                    ELSE 4 END ASC
            ")
            ->orderByDesc('influence_score')
            ->get();

        // Group by region_id for structured consumers; flat list also included
        $grouped = $influences
            ->groupBy(fn ($row) => $row->region_id ?? 'global')
            ->map(fn ($rows) => $rows->values());

        return response()->json([
            'data'       => $influences,
            'grouped'    => $grouped,
            'calculated' => $latestAt,
        ]);
    }
}
