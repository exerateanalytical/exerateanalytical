<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\FederationRegion;
use App\Services\Civic\CivicFeedService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CivicFeedWebController extends Controller
{
    public function __construct(private readonly CivicFeedService $feedService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['region_id', 'type', 'sort']);

        return Inertia::render('Civic/Feed', [
            'feed'    => $this->feedService->getFeed($filters)->values()->all(),
            'regions' => FederationRegion::select('id', 'name', 'code')->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }
}
