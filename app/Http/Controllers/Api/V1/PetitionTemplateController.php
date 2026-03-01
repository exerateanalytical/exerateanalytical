<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PetitionTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PetitionTemplateController extends Controller
{
    /**
     * GET /api/v1/petition-templates
     *
     * Public listing — returns all petition templates, optionally filtered by
     * category or premium flag.  Ordered by sort_order then title.
     */
    public function index(Request $request): JsonResponse
    {
        $templates = PetitionTemplate::query()
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('is_premium'), fn ($q) => $q->where('is_premium', (bool) $request->is_premium))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return response()->json(['data' => $templates]);
    }

    /**
     * GET /api/v1/petition-templates/{template}
     *
     * Public — fetch a single template for preview.
     */
    public function show(PetitionTemplate $petitionTemplate): JsonResponse
    {
        return response()->json(['data' => $petitionTemplate]);
    }

    /**
     * POST /api/v1/petition-templates  (SuperAdmin only)
     */
    public function store(Request $request): JsonResponse
    {
        abort_if(
            ! $request->user()?->hasRole('super_admin'),
            403,
            'Only Super Admins may create petition templates.'
        );

        $validated = $request->validate([
            'title'                   => ['required', 'string', 'max:180'],
            'description'             => ['nullable', 'string'],
            'category'                => ['sometimes', 'string', 'max:40'],
            'summary_template'        => ['nullable', 'string'],
            'body_template'           => ['nullable', 'string'],
            'default_signature_goal'  => ['sometimes', 'integer', 'min:1'],
            'is_premium'              => ['sometimes', 'boolean'],
            'sort_order'              => ['sometimes', 'integer', 'min:0'],
        ]);

        $template = PetitionTemplate::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $template], 201);
    }

    /**
     * PUT /api/v1/petition-templates/{template}  (SuperAdmin only)
     */
    public function update(Request $request, PetitionTemplate $petitionTemplate): JsonResponse
    {
        abort_if(
            ! $request->user()?->hasRole('super_admin'),
            403,
            'Only Super Admins may update petition templates.'
        );

        $validated = $request->validate([
            'title'                   => ['sometimes', 'string', 'max:180'],
            'description'             => ['nullable', 'string'],
            'category'                => ['sometimes', 'string', 'max:40'],
            'summary_template'        => ['nullable', 'string'],
            'body_template'           => ['nullable', 'string'],
            'default_signature_goal'  => ['sometimes', 'integer', 'min:1'],
            'is_premium'              => ['sometimes', 'boolean'],
            'sort_order'              => ['sometimes', 'integer', 'min:0'],
        ]);

        $petitionTemplate->update($validated);

        return response()->json(['data' => $petitionTemplate]);
    }

    /**
     * DELETE /api/v1/petition-templates/{template}  (SuperAdmin only)
     */
    public function destroy(Request $request, PetitionTemplate $petitionTemplate): JsonResponse
    {
        abort_if(
            ! $request->user()?->hasRole('super_admin'),
            403,
            'Only Super Admins may delete petition templates.'
        );

        $petitionTemplate->delete();

        return response()->json(null, 204);
    }
}
