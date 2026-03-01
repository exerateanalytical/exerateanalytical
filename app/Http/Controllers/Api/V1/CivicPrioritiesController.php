<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Civic\CivicSignalPriorityResource;
use App\Models\CivicSignalPriority;
use App\Services\Civic\SignalPriorityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class CivicPrioritiesController extends Controller
{
    public function __construct(
        private readonly SignalPriorityService $service,
    ) {}

    /**
     * GET /api/v1/executive/civic-priorities
     *
     * Return the top-20 civic signals from the most recent computation batch,
     * ordered by priority_score DESC.  Each record is enriched with
     * signal_title and signal_region via a batch hydration pass.
     */
    public function index(): JsonResponse
    {
        $latestAt = CivicSignalPriority::max('calculated_at');

        if (! $latestAt) {
            return response()->json([
                'data' => [],
                'meta' => ['message' => 'No priority data computed yet.'],
            ]);
        }

        // Fetch every record from the latest batch (within a 90-second window
        // to tolerate minor clock drift when multiple workers run together).
        $cutoff = Carbon::parse($latestAt)->subSeconds(90);

        $priorities = CivicSignalPriority::where('calculated_at', '>=', $cutoff)
            ->orderByDesc('priority_score')
            ->limit(20)
            ->get();

        $this->service->hydrateSignals($priorities);

        return response()->json([
            'data' => CivicSignalPriorityResource::collection($priorities),
            'meta' => [
                'calculated_at' => Carbon::parse($latestAt)->toIso8601String(),
                'count'         => $priorities->count(),
            ],
        ]);
    }
}
