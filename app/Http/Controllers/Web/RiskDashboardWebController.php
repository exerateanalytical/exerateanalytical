<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\RiskAlert;
use App\Services\Dashboard\ExecutiveRiskDashboardService;
use App\Services\Risk\RiskIntelligenceService;
use Inertia\Inertia;
use Inertia\Response;

class RiskDashboardWebController extends Controller
{
    public function index(ExecutiveRiskDashboardService $dashService): Response
    {
        $ranking   = $dashService->getRiskRanking();
        $watchlist = $dashService->getAlertWatchlist();

        return Inertia::render('Risk/Dashboard', [
            'ranking'   => $ranking,
            'watchlist' => $watchlist,
        ]);
    }

    public function show(
        Country $country,
        RiskIntelligenceService $riskService,
        ExecutiveRiskDashboardService $dashService
    ): Response {
        $summary = $riskService->getNationalRiskSummary($country->id);
        $drivers = $dashService->getRiskDrivers($country->id);
        $history = $dashService->getRiskHistory($country->id);
        $alerts  = RiskAlert::where('country_id', $country->id)
            ->where('status', 'active')
            ->orderByDesc('triggered_at')
            ->limit(10)
            ->get(['id', 'severity', 'title', 'message', 'triggered_at'])
            ->toArray();

        return Inertia::render('Risk/Country', compact('country', 'summary', 'drivers', 'history', 'alerts'));
    }
}
