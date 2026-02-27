<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Civic\UserTrustResource;
use App\Models\Petition;
use App\Models\PetitionSignature;
use App\Models\PolicyProposal;
use App\Models\PollVote;
use App\Models\User;

class TrustController extends Controller
{
    /**
     * GET /api/v1/users/{user}/trust
     *
     * Returns a user's public trust profile:
     *  - reputation tier (not raw score)
     *  - awarded badges
     *  - total participation count (votes + signatures + petitions created + policies created)
     */
    public function show(User $user): \Illuminate\Http\JsonResponse
    {
        $user->load('badges');

        $participation = $this->resolveParticipationCount($user);

        return response()->json([
            'data' => new UserTrustResource($user),
            'meta' => [
                'participation_count' => $participation,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function resolveParticipationCount(User $user): int
    {
        $votes      = PollVote::where('user_id', $user->id)->count();
        $signatures = PetitionSignature::where('user_id', $user->id)->count();
        $petitions  = Petition::where('creator_id', $user->id)->count();
        $policies   = PolicyProposal::where('creator_id', $user->id)->count();

        return $votes + $signatures + $petitions + $policies;
    }
}
