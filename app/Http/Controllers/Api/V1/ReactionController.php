<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Petition;
use App\Models\Poll;
use App\Models\PolicyProposal;
use App\Services\Civic\ReactionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    /** Maps the short type string sent by clients to the actual Eloquent model class */
    private const REACTABLE_MAP = [
        'poll'     => Poll::class,
        'petition' => Petition::class,
        'policy'   => PolicyProposal::class,
    ];

    public function __construct(
        private readonly ReactionService $service,
    ) {}

    /** POST /api/v1/reactions */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactable_type' => ['required', 'string', 'in:poll,petition,policy'],
            'reactable_id'   => ['required', 'uuid'],
            'type'           => ['required', 'string', 'in:support,oppose,insightful,concern,report'],
        ]);

        $modelClass = self::REACTABLE_MAP[$validated['reactable_type']];

        /** @var Model $reactable */
        $reactable = $modelClass::findOrFail($validated['reactable_id']);

        $reaction = $this->service->react($request->user(), $reactable, $validated['type']);

        return response()->json([
            'data' => [
                'reaction_id'    => $reaction->id,
                'reactable_type' => $validated['reactable_type'],
                'reactable_id'   => $reaction->reactable_id,
                'type'           => $reaction->type,
                'created_at'     => $reaction->created_at?->toIso8601String(),
            ],
            'meta' => ['message' => 'Reaction recorded.'],
        ], 201);
    }
}
