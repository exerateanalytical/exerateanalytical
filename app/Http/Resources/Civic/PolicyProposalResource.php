<?php

namespace App\Http\Resources\Civic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'creator_id' => $this->creator_id,
            'region_id'  => $this->region_id,
            'title'      => $this->title,
            'abstract'   => $this->abstract,
            'full_text'  => $this->full_text,
            'stage'      => $this->stage,
            'status'     => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
