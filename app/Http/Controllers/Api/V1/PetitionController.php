<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Civic\PetitionCollection;
use App\Http\Resources\Civic\PetitionResource;
use App\Models\Petition;
use App\Services\Civic\PetitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PetitionController extends Controller
{
    public function __construct(
        private readonly PetitionService $service,
    ) {}

    /** GET /api/v1/petitions */
    public function index(Request $request): PetitionCollection
    {
        $petitions = Petition::query()
            ->when($request->region_id, fn ($q) => $q->where('region_id', $request->region_id))
            ->where('status', '!=', 'restricted')
            ->latest()
            ->paginate(20);

        return new PetitionCollection($petitions);
    }

    /** POST /api/v1/petitions */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'          => ['required', 'string', 'max:180'],
            'summary'        => ['required', 'string'],
            'body'           => ['required', 'string'],
            'region_id'      => ['nullable', 'uuid'],
            'signature_goal' => ['sometimes', 'integer', 'min:1'],
            'deadline'       => ['nullable', 'date', 'after:now'],
        ]);

        $petition = $this->service->createPetition($request->user(), $validated);

        return response()->json([
            'data' => new PetitionResource($petition),
            'meta' => ['message' => 'Petition created successfully.'],
        ], 201);
    }

    /** POST /api/v1/petitions/{petition}/sign */
    public function sign(Request $request, Petition $petition): JsonResponse
    {
        try {
            $signature = $this->service->sign($request->user(), $petition);
        } catch (RuntimeException $e) {
            return response()->json([
                'data' => null,
                'meta' => ['message' => $e->getMessage()],
            ], 422);
        }

        $petition->refresh();

        return response()->json([
            'data' => [
                'signature_id'    => $signature->id,
                'petition_id'     => $signature->petition_id,
                'signature_count' => $petition->signature_count,
                'status'          => $petition->status,
                'signed_at'       => $signature->signed_at?->toIso8601String(),
            ],
            'meta' => ['message' => 'Petition signed successfully.'],
        ], 201);
    }
}
