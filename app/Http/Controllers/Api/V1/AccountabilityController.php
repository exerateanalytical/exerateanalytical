<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Accountability\AccountabilityEntityResource;
use App\Http\Resources\Accountability\AccountabilityScoreResource;
use App\Models\AccountabilityEntity;
use App\Services\Accountability\AccountabilityMatrixService;
use App\Services\Accountability\AccountabilityReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AccountabilityController extends Controller
{
    public function __construct(
        private readonly AccountabilityMatrixService $service,
        private readonly AccountabilityReadService $readService,
    ) {}

    public function index(string $countryId, int $year): JsonResponse
    {
        $entities = $this->readService->getCountryScores($countryId, $year);

        return response()->json(['data' => AccountabilityEntityResource::collection($entities)]);
    }

    public function showRegional(string $countryId, string $regionId, int $year): JsonResponse
    {
        $entities = $this->readService->getRegionalScores($countryId, $regionId, $year);

        return response()->json(['data' => AccountabilityEntityResource::collection($entities)]);
    }

    public function showInstitution(string $countryId, string $institutionId): JsonResponse
    {
        $entities = $this->readService->getInstitutionScores($countryId, $institutionId);

        return response()->json(['data' => AccountabilityEntityResource::collection($entities)]);
    }

    public function recalculate(string $countryId, string $entityId): JsonResponse
    {
        $entity = AccountabilityEntity::where('id', $entityId)
            ->where('country_id', $countryId)
            ->first();

        if (!$entity) {
            return response()->json(['message' => 'Accountability entity not found.'], 404);
        }

        $score = $this->service->calculateCompositeAccountability($entityId);

        Cache::forget("accountability:{$countryId}:{$entity->year}");

        return response()->json([
            'status' => 'success',
            'message' => 'Accountability score recalculated.',
            'data' => new AccountabilityScoreResource($score),
        ]);
    }
}
