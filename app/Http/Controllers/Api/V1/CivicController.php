<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Civic\StorePetitionRequest;
use App\Http\Resources\Civic\ApprovalRatingResource;
use App\Http\Resources\Civic\PetitionResource;
use App\Models\ApprovalRating;
use App\Models\Petition;
use App\Models\RepresentationRecord;
use App\Services\Civic\PetitionModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CivicController extends Controller
{
    public function __construct(private readonly PetitionModerationService $moderationService) {}

    public function approval(string $countryId, int $year): JsonResponse
    {
        $rating = Cache::remember("civic:approval:{$countryId}:{$year}", 1800, function () use ($countryId, $year) {
            return ApprovalRating::where('country_id', $countryId)
                ->where('year', $year)
                ->whereNull('region_id')
                ->latest('calculated_at')
                ->first();
        });

        if (!$rating) {
            return response()->json(['message' => 'No approval rating found.'], 404);
        }

        return response()->json(['data' => new ApprovalRatingResource($rating)]);
    }

    public function approvalRegional(string $countryId, string $regionId, int $year): JsonResponse
    {
        $rating = ApprovalRating::where('country_id', $countryId)
            ->where('region_id', $regionId)
            ->where('year', $year)
            ->latest('calculated_at')
            ->first();

        if (!$rating) {
            return response()->json(['message' => 'No regional approval rating found.'], 404);
        }

        return response()->json(['data' => new ApprovalRatingResource($rating)]);
    }

    public function representation(string $countryId, int $year): JsonResponse
    {
        $record = RepresentationRecord::where('country_id', $countryId)
            ->where('year', $year)
            ->whereNull('region_id')
            ->latest()
            ->first();

        if (!$record) {
            return response()->json(['message' => 'No representation record found.'], 404);
        }

        return response()->json(['data' => $record]);
    }

    public function petitions(string $countryId): JsonResponse
    {
        $petitions = Cache::remember("civic:petitions:{$countryId}", 1800, function () use ($countryId) {
            return Petition::where('country_id', $countryId)
                ->where('status', 'approved')
                ->latest()
                ->paginate(20);
        });

        return response()->json(['data' => PetitionResource::collection($petitions)]);
    }

    public function storePetition(StorePetitionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $hash = $this->moderationService->generateHash($data['title'], $data['description']);

        $petition = new Petition(array_merge($data, [
            'content_hash' => $hash,
            'status' => 'pending',
            'created_by' => $request->user()?->id,
        ]));

        $issues = $this->moderationService->moderate($petition);
        if (!empty($issues)) {
            return response()->json(['status' => 'rejected', 'issues' => $issues], 422);
        }

        $petition->save();

        Cache::forget("civic:petitions:{$petition->country_id}");

        return (new PetitionResource($petition))->response()->setStatusCode(201);
    }
}
