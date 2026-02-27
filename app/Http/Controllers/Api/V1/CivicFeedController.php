<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Civic\FeedCollection;
use App\Services\Civic\CivicFeedService;
use Illuminate\Http\Request;

class CivicFeedController extends Controller
{
    public function __construct(
        private readonly CivicFeedService $service,
    ) {}

    /** GET /api/v1/feed */
    public function index(Request $request): FeedCollection
    {
        $request->validate([
            'region_id' => ['sometimes', 'uuid'],
            'type'      => ['sometimes', 'string', 'in:poll,petition,policy'],
            'sort'      => ['sometimes', 'string', 'in:recent,impact'],
        ]);

        $feed = $this->service->getFeed($request->only(['region_id', 'type', 'sort']));

        return new FeedCollection($feed);
    }
}
