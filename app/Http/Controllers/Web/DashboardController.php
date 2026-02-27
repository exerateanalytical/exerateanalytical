<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Petition;
use App\Models\Poll;
use App\Models\PolicyProposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $stats = [
            'polls'     => Poll::where('status', 'active')->count(),
            'petitions' => Petition::whereIn('status', ['active', 'milestone_reached'])->count(),
            'policies'  => PolicyProposal::where('status', 'active')->count(),
            'users'     => User::count(),
        ];

        $recent = collect()
            ->merge(
                Poll::where('status', 'active')
                    ->latest()->limit(3)->get()
                    ->map(fn ($p) => [
                        'id'         => $p->id,
                        'type'       => 'poll',
                        'title'      => $p->title,
                        'status'     => $p->status,
                        'created_at' => $p->created_at?->toIso8601String(),
                        'url'        => route('civic.polls.show', $p),
                    ])
            )
            ->merge(
                Petition::whereIn('status', ['active', 'milestone_reached'])
                    ->latest()->limit(3)->get()
                    ->map(fn ($p) => [
                        'id'         => $p->id,
                        'type'       => 'petition',
                        'title'      => $p->title,
                        'status'     => $p->status,
                        'created_at' => $p->created_at?->toIso8601String(),
                        'url'        => route('civic.petitions.show', $p),
                    ])
            )
            ->merge(
                PolicyProposal::where('status', 'active')
                    ->latest()->limit(3)->get()
                    ->map(fn ($p) => [
                        'id'         => $p->id,
                        'type'       => 'policy',
                        'title'      => $p->title,
                        'status'     => $p->stage,
                        'created_at' => $p->created_at?->toIso8601String(),
                        'url'        => route('civic.policies.show', $p),
                    ])
            )
            ->sortByDesc('created_at')
            ->take(6)
            ->values();

        $userTrust = null;
        if ($user = $request->user()) {
            $user->load('badges');
            $userTrust = [
                'tier'   => $user->reputation_tier ?? 'Citizen',
                'badges' => $user->badges->map(fn ($b) => [
                    'code'       => $b->badge_code,
                    'awarded_at' => $b->awarded_at?->toIso8601String(),
                ])->values()->all(),
                'trust_url' => route('trust.profile', $user),
            ];
        }

        return Inertia::render('Dashboard', [
            'stats'     => $stats,
            'recent'    => $recent,
            'userTrust' => $userTrust,
        ]);
    }
}
