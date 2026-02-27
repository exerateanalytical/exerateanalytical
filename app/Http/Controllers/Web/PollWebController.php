<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FederationRegion;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PollWebController extends Controller
{
    public function index(Request $request): Response
    {
        $polls = Poll::with(['creator:id,name,reputation_tier', 'region:id,name,code'])
            ->withCount('reactions')
            ->where('status', '!=', 'restricted')
            ->when($request->region_id, fn ($q) => $q->where('region_id', $request->region_id))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Civic/Polls/Index', [
            'polls'   => $polls,
            'regions' => FederationRegion::select('id', 'name', 'code')->orderBy('name')->get(),
            'filters' => $request->only(['region_id', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Civic/Polls/Create', [
            'regions' => FederationRegion::select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Poll $poll): Response
    {
        abort_if($poll->status === 'restricted', 404);

        $poll->load(['creator:id,name,reputation_tier', 'region:id,name,code'])
             ->loadCount('reactions');

        $userVote = $request->user()
            ? PollVote::where('poll_id', $poll->id)->where('user_id', $request->user()->id)->first()
            : null;

        $reactionCounts = Reaction::where('reactable_id', $poll->id)
            ->where('reactable_type', Poll::class)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $userReaction = $request->user()
            ? Reaction::where('reactable_id', $poll->id)
                ->where('reactable_type', Poll::class)
                ->where('user_id', $request->user()->id)
                ->value('type')
            : null;

        return Inertia::render('Civic/Polls/Show', [
            'poll'           => $poll,
            'userVote'       => $userVote ? ['selected_options' => $userVote->selected_options] : null,
            'reactionCounts' => $reactionCounts,
            'userReaction'   => $userReaction,
        ]);
    }
}
