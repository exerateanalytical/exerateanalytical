<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Governance\GovernanceDecisionRankingResource;
use App\Models\GovernanceDecisionRanking;
use App\Models\GovernanceRecommendation;
use Illuminate\Http\JsonResponse;

/**
 * CT-13 — Decision ranking read endpoint.
 *
 * Returns scored and ranked scenario options for a given recommendation,
 * ordered by rank_position ascending (rank 1 = highest decision score).
 */
class GovernanceDecisionRankingController extends Controller
{
    /**
     * GET /api/v1/executive/recommendations/{recommendation}/decisions
     */
    public function index(GovernanceRecommendation $recommendation): JsonResponse
    {
        $rankings = GovernanceDecisionRanking::where('recommendation_id', $recommendation->id)
            ->orderBy('rank_position')
            ->get();

        if ($rankings->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => ['message' => 'No rankings computed yet. Run governance:rank-decisions to populate.'],
            ]);
        }

        return response()->json([
            'data' => GovernanceDecisionRankingResource::collection($rankings),
            'meta' => [
                'recommendation_id' => $recommendation->id,
                'count'             => $rankings->count(),
            ],
        ]);
    }
}
