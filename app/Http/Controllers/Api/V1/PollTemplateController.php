<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PollTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PollTemplateController extends Controller
{
    /**
     * GET /api/v1/poll-templates
     *
     * Public listing — returns all poll templates, optionally filtered by
     * category or premium flag.  Ordered by sort_order then title.
     */
    public function index(Request $request): JsonResponse
    {
        $templates = PollTemplate::query()
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->filled('is_premium'), fn ($q) => $q->where('is_premium', (bool) $request->is_premium))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return response()->json(['data' => $templates]);
    }

    /**
     * GET /api/v1/poll-templates/{template}
     *
     * Public — fetch a single template for preview.
     */
    public function show(PollTemplate $pollTemplate): JsonResponse
    {
        return response()->json(['data' => $pollTemplate]);
    }

    /**
     * POST /api/v1/poll-templates  (SuperAdmin only)
     */
    public function store(Request $request): JsonResponse
    {
        abort_if(
            ! $request->user()?->hasRole('super_admin'),
            403,
            'Only Super Admins may create poll templates.'
        );

        $validated = $request->validate([
            'title'                => ['required', 'string', 'max:180'],
            'description'          => ['nullable', 'string'],
            'category'             => ['sometimes', 'string', 'max:40'],
            'poll_type'            => ['sometimes', 'in:standard,ranked,weighted,premium'],
            'options'              => ['required', 'array', 'min:2'],
            'options.*'            => ['required', 'string', 'max:200'],
            'allow_multiple_votes' => ['sometimes', 'boolean'],
            'verified_only'        => ['sometimes', 'boolean'],
            'is_premium'           => ['sometimes', 'boolean'],
            'sort_order'           => ['sometimes', 'integer', 'min:0'],
        ]);

        $template = PollTemplate::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $template], 201);
    }

    /**
     * PUT /api/v1/poll-templates/{template}  (SuperAdmin only)
     */
    public function update(Request $request, PollTemplate $pollTemplate): JsonResponse
    {
        abort_if(
            ! $request->user()?->hasRole('super_admin'),
            403,
            'Only Super Admins may update poll templates.'
        );

        $validated = $request->validate([
            'title'                => ['sometimes', 'string', 'max:180'],
            'description'          => ['nullable', 'string'],
            'category'             => ['sometimes', 'string', 'max:40'],
            'poll_type'            => ['sometimes', 'in:standard,ranked,weighted,premium'],
            'options'              => ['sometimes', 'array', 'min:2'],
            'options.*'            => ['required_with:options', 'string', 'max:200'],
            'allow_multiple_votes' => ['sometimes', 'boolean'],
            'verified_only'        => ['sometimes', 'boolean'],
            'is_premium'           => ['sometimes', 'boolean'],
            'sort_order'           => ['sometimes', 'integer', 'min:0'],
        ]);

        $pollTemplate->update($validated);

        return response()->json(['data' => $pollTemplate]);
    }

    /**
     * DELETE /api/v1/poll-templates/{template}  (SuperAdmin only)
     */
    public function destroy(Request $request, PollTemplate $pollTemplate): JsonResponse
    {
        abort_if(
            ! $request->user()?->hasRole('super_admin'),
            403,
            'Only Super Admins may delete poll templates.'
        );

        $pollTemplate->delete();

        return response()->json(null, 204);
    }
}
