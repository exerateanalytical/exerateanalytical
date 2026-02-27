<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Civic\PolicyProposalCollection;
use App\Http\Resources\Civic\PolicyProposalResource;
use App\Models\PolicyProposal;
use App\Services\Civic\PolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PolicyProposalController extends Controller
{
    public function __construct(
        private readonly PolicyService $service,
    ) {}

    /** GET /api/v1/policies */
    public function index(Request $request): PolicyProposalCollection
    {
        $proposals = PolicyProposal::query()
            ->when($request->region_id, fn ($q) => $q->where('region_id', $request->region_id))
            ->when($request->stage,     fn ($q) => $q->where('stage', $request->stage))
            ->where('status', '!=', 'restricted')
            ->latest()
            ->paginate(20);

        return new PolicyProposalCollection($proposals);
    }

    /** POST /api/v1/policies */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'     => ['required', 'string', 'max:200'],
            'abstract'  => ['required', 'string'],
            'full_text' => ['required', 'string'],
            'region_id' => ['nullable', 'uuid'],
        ]);

        $proposal = $this->service->createProposal($request->user(), $validated);

        return response()->json([
            'data' => new PolicyProposalResource($proposal),
            'meta' => ['message' => 'Policy proposal created successfully.'],
        ], 201);
    }

    /** PATCH /api/v1/policies/{policy}/stage */
    public function changeStage(Request $request, PolicyProposal $policy): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:public_consultation,revision,finalized,archived'],
        ]);

        try {
            $proposal = $this->service->changeStage($request->user(), $policy, $validated['stage']);
        } catch (RuntimeException $e) {
            return response()->json([
                'data' => null,
                'meta' => ['message' => $e->getMessage()],
            ], $e->getCode() === 403 ? 403 : 422);
        }

        return response()->json([
            'data' => new PolicyProposalResource($proposal),
            'meta' => ['message' => 'Stage advanced to ' . $proposal->stage . '.'],
        ]);
    }
}
