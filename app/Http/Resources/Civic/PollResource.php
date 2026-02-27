<?php

namespace App\Http\Resources\Civic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'creator_id'           => $this->creator_id,
            'region_id'            => $this->region_id,
            'title'                => $this->title,
            'description'          => $this->description,
            'visibility'           => $this->visibility,
            'type'                 => $this->type,
            'options'              => $this->options,
            'starts_at'            => $this->starts_at?->toIso8601String(),
            'ends_at'              => $this->ends_at?->toIso8601String(),
            'allow_multiple_votes' => $this->allow_multiple_votes,
            'verified_only'        => $this->verified_only,
            'total_votes'          => $this->total_votes,
            'status'               => $this->status,
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
