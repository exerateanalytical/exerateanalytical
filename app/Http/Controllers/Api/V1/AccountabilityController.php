<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Accountability\AccountabilityEntityResource;
use App\Models\AccountabilityEntity;
use App\Models\Institution;
use App\Services\Accountability\AccountabilityMatrixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AccountabilityController extends Controller
{
    public function __construct(private readonly AccountabilityMatrixService $service) {}

    public function index(string $countryId, int $year): JsonResponse
    {
        $entities = Cache::remember("accountability:{$countryId}:{$year}", 3600, function () use ($countryId, $year) {
            return AccountabilityEntity::with(['institution', 'score'])
                ->where('country_id', $countryId)
                ->where('year', $year)
                ->get();
        });

        return response()->json(['data' => AccountabilityEntityResource::collection($entities)]);
    }

    public function showRegional(string $countryId, string $regionId, int $year): JsonResponse
    {
        $entities = AccountabilityEntity::with(['institution', 'score'])
            ->where('country_id', $countryId)
            ->where('region_id', $regionId)
            ->where('year', $year)
            ->get();

        return response()->json(['data' => AccountabilityEntityResource::collection($entities)]);
    }

    public function showInstitution(string $countryId, string $institutionId): JsonResponse
    {
        $entities = AccountabilityEntity::with(['score', 'links'])
            ->where('country_id', $countryId)
            ->where('institution_id', $institutionId)
            ->orderBy('year', 'desc')
            ->get();

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

        return response()->json([
            'status' => 'success',
            'message' => 'Accountability score recalculated.',
            'data' => $score,
        ]);
    }
}
