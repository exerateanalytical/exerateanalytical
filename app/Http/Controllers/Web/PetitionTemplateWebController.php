<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PetitionTemplate;
use Inertia\Inertia;
use Inertia\Response;

class PetitionTemplateWebController extends Controller
{
    /**
     * GET /civic/petitions/templates
     *
     * Public gallery — all petition templates, pre-grouped by is_premium for
     * the frontend to render a "Featured" section at the top.
     */
    public function index(): Response
    {
        $templates = PetitionTemplate::orderBy('sort_order')->orderBy('title')->get();

        return Inertia::render('Civic/Petitions/Templates', [
            'templates' => $templates,
        ]);
    }
}
