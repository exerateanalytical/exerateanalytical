<?php

namespace App\Http\Resources\Civic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the aggregated civic feed collection returned by CivicFeedService.
 * The feed items are plain arrays (not Eloquent models), so we extend
 * JsonResource and wrap the collection manually with data/meta.
 */
class FeedCollection extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->resource->values()->all(),
            'meta' => [
                'total' => $this->resource->count(),
            ],
        ];
    }
}
