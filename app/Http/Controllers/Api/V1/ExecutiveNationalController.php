<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Risk\ExecutiveNationalResource;
use App\Services\Risk\ExposureMatrixService;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Http\JsonResponse;

class ExecutiveNationalController extends Controller
{
    public function __construct(
        private readonly RiskIntelligenceService $service,
        private readonly ExposureMatrixService $matrixService,
    ) {}

    public function show(string $countryId): JsonResponse
    {
        $summary        = $this->service->getNationalRiskSummary($countryId);
        $exposureMatrix = $this->matrixService->getActiveMatrix();

        $systemicEntry   = null;
        $rankingPosition = null;
        $totalCountries  = null;

        if ($exposureMatrix !== null) {
            $ranked         = $this->service->computeSystemicImportance($exposureMatrix);
            $totalCountries = count($ranked);

            foreach ($ranked as $index => $entry) {
                if ($entry['country_id'] === $countryId) {
                    $rankingPosition = $index + 1;
                    $systemicEntry   = $entry;
                    break;
                }
            }
        }

        return response()->json([
            'data' => new ExecutiveNationalResource([
                'country_id'       => $countryId,
                'summary'          => $summary,
                'systemic_entry'   => $systemicEntry,
                'ranking_position' => $rankingPosition,
                'total_countries'  => $totalCountries,
            ]),
        ]);
    }
}
