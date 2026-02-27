<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Civic\PollCollection;
use App\Http\Resources\Civic\PollResource;
use App\Models\Poll;
use App\Services\Civic\PollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PollController extends Controller
{
    public function __construct(
        private readonly PollService $service,
    ) {}

    /** GET /api/v1/polls */
    public function index(Request $request): PollCollection
    {
        $polls = Poll::query()
            ->when($request->region_id, fn ($q) => $q->where('region_id', $request->region_id))
            ->when($request->status,    fn ($q) => $q->where('status', $request->status))
            ->where('status', '!=', 'restricted')
            ->latest()
            ->paginate(20);

        return new PollCollection($polls);
    }

    /** POST /api/v1/polls */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'                => ['required', 'string', 'max:180'],
            'description'          => ['nullable', 'string'],
            'region_id'            => ['nullable', 'uuid'],
            'visibility'           => ['sometimes', 'in:public,regional,private'],
            'type'                 => ['sometimes', 'in:standard,ranked,weighted,premium'],
            'options'              => ['required', 'array', 'min:2'],
            'options.*'            => ['required', 'string'],
            'starts_at'            => ['nullable', 'date'],
            'ends_at'              => ['nullable', 'date', 'after:starts_at'],
            'allow_multiple_votes' => ['sometimes', 'boolean'],
            'verified_only'        => ['sometimes', 'boolean'],
        ]);

        $poll = $this->service->createPoll($request->user(), $validated);

        return response()->json([
            'data' => new PollResource($poll),
            'meta' => ['message' => 'Poll created successfully.'],
        ], 201);
    }

    /** POST /api/v1/polls/{poll}/vote */
    public function vote(Request $request, Poll $poll): JsonResponse
    {
        $validated = $request->validate([
            'selected_options'   => ['required', 'array', 'min:1'],
            'selected_options.*' => ['required', 'string'],
        ]);

        try {
            $vote = $this->service->vote($request->user(), $poll, $validated['selected_options']);
        } catch (RuntimeException $e) {
            return response()->json([
                'data'  => null,
                'meta'  => ['message' => $e->getMessage()],
            ], $e->getCode() === 403 ? 403 : 422);
        }

        return response()->json([
            'data' => [
                'vote_id'     => $vote->id,
                'poll_id'     => $vote->poll_id,
                'voted_at'    => $vote->voted_at?->toIso8601String(),
            ],
            'meta' => ['message' => 'Vote recorded successfully.'],
        ], 201);
    }
}
