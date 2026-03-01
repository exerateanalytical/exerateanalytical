<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PollTemplate;
use Inertia\Inertia;
use Inertia\Response;

class PollTemplateWebController extends Controller
{
    /**
     * GET /civic/polls/templates
     *
     * Public gallery — all poll templates, pre-grouped by is_premium for
     * the frontend to render a "Featured" section at the top.
     */
    public function index(): Response
    {
        $templates = PollTemplate::orderBy('sort_order')->orderBy('title')->get();

        return Inertia::render('Civic/Polls/Templates', [
            'templates' => $templates,
        ]);
    }
}
