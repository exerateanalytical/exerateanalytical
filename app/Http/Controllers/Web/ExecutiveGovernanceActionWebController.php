<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GovernanceAction;
use Inertia\Inertia;
use Inertia\Response;

class ExecutiveGovernanceActionWebController extends Controller
{
    /**
     * GET /executive/actions
     *
     * Latest governance actions with actor eager-loaded.
     * Paginated 20 per page, ordered by most-recent execution.
     */
    public function index(): Response
    {
        $actions = GovernanceAction::with('actor:id,name')
            ->latest('executed_at')
            ->paginate(20);

        return Inertia::render('Executive/Actions', compact('actions'));
    }
}
