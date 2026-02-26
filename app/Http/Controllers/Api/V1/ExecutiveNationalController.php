<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Risk\ExecutiveNationalResource;
use App\Models\Country;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Http\JsonResponse;

class ExecutiveNationalController extends Controller
{
    public function __construct(private readonly RiskIntelligenceService $service) {}

    public function show(string $countryId): JsonResponse
    {
        // National risk summary for the target country
        $summary = $this->service->getNationalRiskSummary($countryId);

        // Build a full-mesh exposure matrix over all active countries so that
        // every country receives a non-trivial outgoing_exposure_weight and
        // systemic_importance_score, making the ranking meaningful.
        $allIds         = Country::active()->pluck('id')->all();
        $exposureMatrix = [];

        foreach ($allIds as $id) {
            $outgoing = [];
            foreach ($allIds as $otherId) {
                if ($otherId !== $id) {
                    $outgoing[$otherId] = 1.0;
                }
            }
            $exposureMatrix[$id] = $outgoing;
        }

        // Sorted descending by systemic_importance_score
        $ranked = $this->service->computeSystemicImportance($exposureMatrix);

        // Determine 1-based ranking position of the target country
        $rankingPosition = null;
        $systemicEntry   = null;

        foreach ($ranked as $index => $entry) {
            if ($entry['country_id'] === $countryId) {
                $rankingPosition = $index + 1;
                $systemicEntry   = $entry;
                break;
            }
        }

        return response()->json([
            'data' => new ExecutiveNationalResource([
                'country_id'      => $countryId,
                'summary'         => $summary,
                'systemic_entry'  => $systemicEntry,
                'ranking_position' => $rankingPosition,
                'total_countries' => count($ranked),
            ]),
        ]);
    }
}
