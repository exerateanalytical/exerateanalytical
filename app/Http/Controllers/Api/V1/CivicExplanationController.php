<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Civic\CivicSignalExplanationResource;
use App\Models\CivicSignalExplanation;
use App\Models\CivicSignalPriority;
use Illuminate\Http\JsonResponse;

/**
 * CT-12 — Causality explanation endpoint.
 *
 * Returns the latest explanation snapshot for a given civic signal priority.
 */
class CivicExplanationController extends Controller
{
    /**
     * GET /api/v1/executive/civic-priorities/{priority}/explanation
     */
    public function show(CivicSignalPriority $priority): JsonResponse
    {
        $explanation = CivicSignalExplanation::where('signal_priority_id', $priority->id)
            ->latest('created_at')
            ->first();

        if (! $explanation) {
            return response()->json([
                'data'  => null,
                'meta'  => ['message' => 'No explanation generated yet for this signal. Run civic:generate-signal-explanations to populate.'],
            ], 404);
        }

        return response()->json([
            'data' => new CivicSignalExplanationResource($explanation),
        ]);
    }
}
