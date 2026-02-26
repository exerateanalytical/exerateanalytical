<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExposureMatrix\StoreExposureMatrixRequest;
use App\Http\Resources\Risk\ExposureMatrixResource;
use App\Models\ExposureMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ExposureMatrixController extends Controller
{
    public function store(StoreExposureMatrixRequest $request): JsonResponse
    {
        $matrix = ExposureMatrix::create($request->validated());

        return (new ExposureMatrixResource($matrix))
            ->response()
            ->setStatusCode(201);
    }

    public function activate(string $id): JsonResponse
    {
        $matrix = ExposureMatrix::findOrFail($id);

        DB::transaction(function () use ($matrix) {
            ExposureMatrix::where('active', true)->update(['active' => false]);
            $matrix->update(['active' => true]);
        });

        return response()->json([
            'data' => new ExposureMatrixResource($matrix->fresh()),
        ]);
    }

    public function active(): JsonResponse
    {
        $matrix = ExposureMatrix::where('active', true)->first();

        return response()->json([
            'data' => $matrix ? new ExposureMatrixResource($matrix) : null,
        ]);
    }
}
