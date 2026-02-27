<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FederationRegion;
use App\Models\Petition;
use App\Models\PetitionSignature;
use App\Models\Reaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PetitionWebController extends Controller
{
    public function index(Request $request): Response
    {
        $petitions = Petition::with(['creator:id,name,reputation_tier', 'region:id,name,code'])
            ->where('status', '!=', 'restricted')
            ->when($request->region_id, fn ($q) => $q->where('region_id', $request->region_id))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Civic/Petitions/Index', [
            'petitions' => $petitions,
            'regions'   => FederationRegion::select('id', 'name', 'code')->orderBy('name')->get(),
            'filters'   => $request->only(['region_id', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Civic/Petitions/Create', [
            'regions' => FederationRegion::select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Petition $petition): Response
    {
        abort_if($petition->status === 'restricted', 404);

        $petition->load(['creator:id,name,reputation_tier', 'region:id,name,code'])
                 ->loadCount('reactions');

        $hasSigned = $request->user()
            ? PetitionSignature::where('petition_id', $petition->id)
                ->where('user_id', $request->user()->id)
                ->exists()
            : false;

        $reactionCounts = Reaction::where('reactable_id', $petition->id)
            ->where('reactable_type', Petition::class)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $userReaction = $request->user()
            ? Reaction::where('reactable_id', $petition->id)
                ->where('reactable_type', Petition::class)
                ->where('user_id', $request->user()->id)
                ->value('type')
            : null;

        return Inertia::render('Civic/Petitions/Show', [
            'petition'       => $petition,
            'hasSigned'      => $hasSigned,
            'reactionCounts' => $reactionCounts,
            'userReaction'   => $userReaction,
        ]);
    }
}
