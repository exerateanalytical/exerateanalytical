<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Inertia\Inertia;
use Inertia\Response;

class AdminWebController extends Controller
{
    public function dashboard(): Response
    {
        try {
            $stats = [
                'countries'    => \App\Models\Country::count(),
                'regions'      => \App\Models\Region::count(),
                'institutions' => \App\Models\Institution::count(),
                'activeAlerts' => \App\Models\RiskAlert::where('status', 'active')->count(),
                'users'        => \App\Models\User::count(),
                'polls'        => \App\Models\Poll::count(),
                'petitions'    => \App\Models\Petition::count(),
                'policies'     => \App\Models\PolicyProposal::count(),
            ];
        } catch (\Throwable $e) {
            $stats = [
                'countries'    => 0,
                'regions'      => 0,
                'institutions' => 0,
                'activeAlerts' => 0,
                'users'        => 0,
                'polls'        => 0,
                'petitions'    => 0,
                'policies'     => 0,
            ];
        }

        try {
            $recentAuditLogs = \App\Models\AuditLog::orderByDesc('created_at')
                ->limit(10)
                ->get(['id', 'event', 'user_id', 'auditable_type', 'auditable_id', 'created_at'])
                ->toArray();
        } catch (\Throwable $e) {
            $recentAuditLogs = [];
        }

        return Inertia::render('Admin/Dashboard', compact('stats', 'recentAuditLogs'));
    }

    public function countries(): Response
    {
        try {
            $countries = \App\Models\Country::with('federationRegion:id,name,code')
                ->withCount(['regions', 'institutions'])
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
        } catch (\Throwable $e) {
            $countries = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        try {
            $federationRegions = \App\Models\FederationRegion::select('id', 'name', 'code')
                ->orderBy('name')
                ->get();
        } catch (\Throwable $e) {
            $federationRegions = collect();
        }

        return Inertia::render('Admin/Countries/Index', compact('countries', 'federationRegions'));
    }

    public function createCountry(): Response
    {
        try {
            $federationRegions = \App\Models\FederationRegion::select('id', 'name', 'code')
                ->orderBy('name')
                ->get();
        } catch (\Throwable $e) {
            $federationRegions = collect();
        }

        return Inertia::render('Admin/Countries/Form', [
            'country'           => null,
            'federationRegions' => $federationRegions,
            'riskTiers'         => ['low', 'moderate', 'high'],
        ]);
    }

    public function editCountry(Country $country): Response
    {
        try {
            $federationRegions = \App\Models\FederationRegion::select('id', 'name', 'code')
                ->orderBy('name')
                ->get();
        } catch (\Throwable $e) {
            $federationRegions = collect();
        }

        return Inertia::render('Admin/Countries/Form', [
            'country'           => $country,
            'federationRegions' => $federationRegions,
            'riskTiers'         => ['low', 'moderate', 'high'],
        ]);
    }

    public function countryRegions(Country $country): Response
    {
        $country->load('regions');
        return Inertia::render('Admin/Regions/Index', [
            'country' => $country,
            'regions' => $country->regions,
        ]);
    }

    public function exposureMatrix(): Response
    {
        try {
            $matrices = \App\Models\ExposureMatrix::orderByDesc('created_at')->limit(20)->get();
        } catch (\Throwable $e) {
            $matrices = collect();
        }
        return Inertia::render('Admin/ExposureMatrix', ['matrices' => $matrices]);
    }

    public function alertSubscriptions(): Response
    {
        try {
            $subscriptions = \App\Models\RiskAlertSubscription::with('country:id,name,iso_code')
                ->orderByDesc('created_at')->limit(50)->get();
        } catch (\Throwable $e) {
            $subscriptions = collect();
        }
        return Inertia::render('Admin/AlertSubscriptions', ['subscriptions' => $subscriptions]);
    }

    public function riskContagion(): Response
    {
        try {
            $regions    = \App\Models\FederationRegion::select('id', 'name', 'code')->orderBy('name')->get();
            $recentRuns = \App\Models\RiskContagionRun::orderByDesc('created_at')->limit(10)->get();
        } catch (\Throwable $e) {
            $regions    = collect();
            $recentRuns = collect();
        }
        return Inertia::render('Admin/RiskContagion', compact('regions', 'recentRuns'));
    }
}
