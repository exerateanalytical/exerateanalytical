<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GovernanceRecommendation;
use App\Services\Executive\ControlTowerService;
use Inertia\Inertia;
use Inertia\Response;

class ExecutiveControlTowerWebController extends Controller
{
    /**
     * GET /executive/control-tower
     *
     * Renders the Control Tower dashboard, seeding data via Inertia props from
     * the same ControlTowerService used by the API endpoint. The Vue page can
     * refresh live via GET /api/v1/executive/control-tower.
     */
    public function index(ControlTowerService $service): Response
    {
        $payload = $service->build();

        // Seed active recommendations ordered critical → low.
        $recommendations = GovernanceRecommendation::where('status', 'active')
            ->orderByRaw(GovernanceRecommendation::severityWeight() . ' DESC')
            ->orderByDesc('confidence_score')
            ->get();

        return Inertia::render('Executive/ControlTower', [
            'hero'                 => $payload['hero'],
            'regions'              => $payload['regions'],
            'policy_radar'         => $payload['policy_radar'],
            'civic_momentum'       => $payload['civic_momentum'],
            'contagion_forecast'   => $payload['contagion_forecast'],
            'representation_index' => $payload['representation_index'],
            'recommendations'      => $recommendations,
            'generated_at'         => now()->toIso8601String(),
        ]);
    }
}
