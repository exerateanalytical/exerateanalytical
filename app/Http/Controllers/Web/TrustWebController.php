<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Petition;
use App\Models\PetitionSignature;
use App\Models\PolicyProposal;
use App\Models\Poll;
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

        // Recent activity: last 5 of each created type
        $createdPolls = Poll::where('creator_id', $user->id)
            ->select('id', 'title', 'status', 'created_at')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($p) => ['type' => 'poll', 'id' => $p->id, 'title' => $p->title, 'status' => $p->status, 'created_at' => $p->created_at?->toIso8601String()]);

        $createdPetitions = Petition::where('creator_id', $user->id)
            ->select('id', 'title', 'status', 'created_at')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($p) => ['type' => 'petition', 'id' => $p->id, 'title' => $p->title, 'status' => $p->status, 'created_at' => $p->created_at?->toIso8601String()]);

        $createdPolicies = PolicyProposal::where('creator_id', $user->id)
            ->select('id', 'title', 'status', 'created_at')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($p) => ['type' => 'policy', 'id' => $p->id, 'title' => $p->title, 'status' => $p->status, 'created_at' => $p->created_at?->toIso8601String()]);

        $activity = $createdPolls
            ->concat($createdPetitions)
            ->concat($createdPolicies)
            ->sortByDesc('created_at')
            ->values()
            ->all();

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
            'activity'      => $activity,
        ]);
    }
}
