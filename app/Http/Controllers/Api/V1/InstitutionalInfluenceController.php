<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InstitutionalInfluence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InstitutionalInfluenceController extends Controller
{
    /**
     * GET /api/v1/executive/actors/influence
     *
     * Returns the latest computed institutional influence snapshot.
     * Requires authentication — executive visibility only.
     * Read-only analytics; no rankings are exposed publicly.
     */
    public function index(Request $request): JsonResponse
    {
        abort_if(
            ! $request->user()?->hasRole('SuperAdmin')
            && ! $request->user()?->hasRole('Governor'),
            403,
            'Executive access required.'
        );

        $latestAt = InstitutionalInfluence::max('calculated_at');

        if (! $latestAt) {
            return response()->json([
                'data'        => [],
                'grouped'     => [],
                'calculated'  => null,
            ]);
        }

        $cutoff = Carbon::parse($latestAt)->subSeconds(60);

        $actors = InstitutionalInfluence::with('actor:id,name,email', 'region:id,code,name')
            ->where('calculated_at', '>=', $cutoff)
            ->orderByDesc('influence_score')
            ->get();

        // Group by region_id (null → 'global')
        $grouped = $actors
            ->groupBy(fn ($row) => $row->region_id ?? 'global')
            ->map(fn ($rows) => $rows->values());

        return response()->json([
            'data'       => $actors,
            'grouped'    => $grouped,
            'calculated' => $latestAt,
        ]);
    }
}
