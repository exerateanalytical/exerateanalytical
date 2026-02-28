<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GovernanceInfluence;
use App\Models\GovernanceRecommendation;
use App\Models\InstitutionalInfluence;
use App\Services\Executive\ControlTowerService;
use Illuminate\Support\Carbon;
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

        // Seed latest influence signals (most recent batch, grouped by type then score).
        $latestInfluenceAt = GovernanceInfluence::max('calculated_at');
        $influences = collect();
        if ($latestInfluenceAt) {
            $cutoff = Carbon::parse($latestInfluenceAt)->subSeconds(60);
            $influences = GovernanceInfluence::with('region:id,code,name')
                ->where('calculated_at', '>=', $cutoff)
                ->orderByRaw("CASE influence_type
                    WHEN 'contagion_pressure' THEN 1
                    WHEN 'trust_shift'        THEN 2
                    WHEN 'participation_gap'  THEN 3
                    ELSE 4 END ASC")
                ->orderByDesc('influence_score')
                ->get();
        }

        // Seed latest institutional actor influence snapshot (executive-only).
        $latestActorAt = InstitutionalInfluence::max('calculated_at');
        $actors = collect();
        if ($latestActorAt) {
            $cutoff = Carbon::parse($latestActorAt)->subSeconds(60);
            $actors = InstitutionalInfluence::with('actor:id,name,email', 'region:id,code,name')
                ->where('calculated_at', '>=', $cutoff)
                ->orderByDesc('influence_score')
                ->get();
        }

        return Inertia::render('Executive/ControlTower', [
            'hero'                 => $payload['hero'],
            'regions'              => $payload['regions'],
            'policy_radar'         => $payload['policy_radar'],
            'civic_momentum'       => $payload['civic_momentum'],
            'contagion_forecast'   => $payload['contagion_forecast'],
            'representation_index' => $payload['representation_index'],
            'recommendations'      => $recommendations,
            'influences'           => $influences,
            'actors'               => $actors,
            'generated_at'         => now()->toIso8601String(),
        ]);
    }
}
