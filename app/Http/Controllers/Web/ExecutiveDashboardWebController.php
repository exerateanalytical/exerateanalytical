<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\RiskAlert;
use App\Models\RiskAlertEvent;
use App\Services\Dashboard\ExecutiveRiskDashboardService;
use App\Services\Risk\RiskGovernanceMetricsService;
use App\Services\Risk\RiskIntelligenceService;
use Inertia\Inertia;
use Inertia\Response;

class ExecutiveDashboardWebController extends Controller
{
    public function index(ExecutiveRiskDashboardService $dashService): Response
    {
        $ranking   = $dashService->getRiskRanking();
        $watchlist = $dashService->getAlertWatchlist();
        $countries = Country::select('id', 'name', 'iso_code')->active()->orderBy('name')->get();

        return Inertia::render('Executive/Index', compact('ranking', 'watchlist', 'countries'));
    }

    public function show(Country $country, RiskIntelligenceService $riskService, ExecutiveRiskDashboardService $dashService, RiskGovernanceMetricsService $govMetrics): Response
    {
        $summary = $riskService->getNationalRiskSummary($country->id);
        $drivers = $dashService->getRiskDrivers($country->id);

        try {
            $govData = $govMetrics->getMetrics($country->id);
        } catch (\Throwable $e) {
            $govData = [];
        }

        $alerts = RiskAlert::where('country_id', $country->id)
            ->where('status', 'active')
            ->orderByDesc('triggered_at')
            ->limit(10)
            ->get(['id', 'severity', 'title', 'message', 'triggered_at'])
            ->toArray();

        $recentAlertEvents = RiskAlertEvent::where('country_id', $country->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'alert_type', 'severity', 'triggered_at', 'acknowledged_at', 'acknowledged_by'])
            ->toArray();

        return Inertia::render('Executive/Country', compact('country', 'summary', 'drivers', 'govData', 'alerts', 'recentAlertEvents'));
    }
}
