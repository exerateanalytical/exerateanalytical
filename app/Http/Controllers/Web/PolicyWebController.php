<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FederationRegion;
use App\Models\PolicyProposal;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PolicyWebController extends Controller
{
    private const STAGE_ORDER = [
        'draft', 'public_consultation', 'revision', 'finalized', 'archived',
    ];

    private const STAGE_NEXT = [
        'draft'               => 'public_consultation',
        'public_consultation' => 'revision',
        'revision'            => 'finalized',
        'finalized'           => 'archived',
    ];

    public function index(Request $request): Response
    {
        $proposals = PolicyProposal::with(['creator:id,name,reputation_tier', 'region:id,name,code'])
            ->withCount('reactions')
            ->where('status', '!=', 'restricted')
            ->when($request->region_id, fn ($q) => $q->where('region_id', $request->region_id))
            ->when($request->stage,     fn ($q) => $q->where('stage', $request->stage))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Civic/Policy/Index', [
            'proposals' => $proposals,
            'regions'   => FederationRegion::select('id', 'name', 'code')->orderBy('name')->get(),
            'filters'   => $request->only(['region_id', 'stage']),
            'stages'    => self::STAGE_ORDER,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Civic/Policy/Create', [
            'regions' => FederationRegion::select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, PolicyProposal $policy): Response
    {
        abort_if($policy->status === 'restricted', 404);

        $policy->load(['creator:id,name,reputation_tier', 'region:id,name,code'])
               ->loadCount('reactions');

        $isCreator = $request->user()?->id === $policy->creator_id;
        $nextStage = self::STAGE_NEXT[$policy->stage] ?? null;

        $reactionCounts = Reaction::where('reactable_id', $policy->id)
            ->where('reactable_type', PolicyProposal::class)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $userReaction = $request->user()
            ? Reaction::where('reactable_id', $policy->id)
                ->where('reactable_type', PolicyProposal::class)
                ->where('user_id', $request->user()->id)
                ->value('type')
            : null;

        return Inertia::render('Civic/Policy/Show', [
            'policy'         => $policy,
            'stages'         => self::STAGE_ORDER,
            'nextStage'      => $nextStage,
            'isCreator'      => $isCreator,
            'reactionCounts' => $reactionCounts,
            'userReaction'   => $userReaction,
        ]);
    }
}
