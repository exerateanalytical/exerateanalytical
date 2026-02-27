<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\GovernanceScore;
use App\Models\RiskAlert;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CountriesWebController extends Controller
{
    public function index(Request $request): Response
    {
        $countries = Country::with('federationRegion:id,name,code')
            ->active()
            ->when($request->risk_tier, fn ($q) => $q->where('risk_tier', $request->risk_tier))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")->orWhere('iso_code', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Countries/Index', [
            'countries' => $countries,
            'filters'   => $request->only(['risk_tier', 'search']),
            'riskTiers' => ['low', 'moderate', 'high'],
        ]);
    }

    public function show(Country $country): Response
    {
        $country->load([
            'regions:id,country_id,name,administrative_level,population',
            'institutions:id,country_id,name,type',
        ]);

        $latestGovernance = GovernanceScore::where('country_id', $country->id)
            ->whereNull('region_id')
            ->latest('year')
            ->first();

        $riskAlerts = RiskAlert::where('country_id', $country->id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'severity', 'title', 'message', 'triggered_at']);

        return Inertia::render('Countries/Show', [
            'country'    => $country,
            'governance' => $latestGovernance,
            'alerts'     => $riskAlerts,
        ]);
    }
}
