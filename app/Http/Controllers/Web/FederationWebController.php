<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Federation\FederationAggregationService;
use App\Services\Federation\RegionalSystemicSnapshotService;
use Inertia\Inertia;
use Inertia\Response;

class FederationWebController extends Controller
{
    public function index(FederationAggregationService $fedService, RegionalSystemicSnapshotService $snapshotService): Response
    {
        $global = \App\Models\FederatedGlobalSnapshot::latest('snapshot_at')->first();
        $regional = \App\Models\RegionalSystemicSnapshot::with('federationRegion:id,name,code')->latest('snapshot_at')->get()->groupBy('region_id')->map->first()->values();
        $regions = \App\Models\FederationRegion::select('id', 'name', 'code')->orderBy('name')->get();

        return Inertia::render('Federation/Index', [
            'globalSnapshot'    => $global,
            'regionalSnapshots' => $regional,
            'regions'           => $regions,
        ]);
    }
}
