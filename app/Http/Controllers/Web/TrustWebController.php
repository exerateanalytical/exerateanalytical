<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Petition;
use App\Models\PetitionSignature;
use App\Models\PolicyProposal;
use App\Models\PollVote;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class TrustWebController extends Controller
{
    public function show(User $user): Response
    {
        $user->load('badges');

        $participation = [
            'votes'      => PollVote::where('user_id', $user->id)->count(),
            'signatures' => PetitionSignature::where('user_id', $user->id)->count(),
            'petitions'  => Petition::where('creator_id', $user->id)->count(),
            'policies'   => PolicyProposal::where('creator_id', $user->id)->count(),
        ];

        $participation['total'] = array_sum($participation);

        return Inertia::render('Trust/Profile', [
            'profile'       => [
                'id'              => $user->id,
                'name'            => $user->name,
                'reputation_tier' => $user->reputation_tier ?? 'Citizen',
                'badges'          => $user->badges->map(fn ($b) => [
                    'code'       => $b->badge_code,
                    'awarded_at' => $b->awarded_at?->toIso8601String(),
                ])->values()->all(),
            ],
            'participation' => $participation,
        ]);
    }
}
