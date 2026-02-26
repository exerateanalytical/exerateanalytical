<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Risk\ExposureMatrixService;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Http\JsonResponse;

class ExecutiveNetworkRankingController extends Controller
{
    public function __construct(
        private readonly RiskIntelligenceService $riskService,
        private readonly ExposureMatrixService $matrixService,
    ) {}

    public function index(): JsonResponse
    {
        $matrix = $this->matrixService->getActiveMatrix();

        if ($matrix === null) {
            return response()->json([
                'data'    => [],
                'message' => 'No active exposure matrix',
            ]);
        }

        return response()->json([
            'data' => $this->riskService->computeSystemicImportance($matrix),
        ]);
    }
}
