<?php

namespace App\Http\Resources\Civic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'creator_id'      => $this->creator_id,
            'region_id'       => $this->region_id,
            'title'           => $this->title,
            'summary'         => $this->summary,
            'body'            => $this->body,
            'signature_goal'  => $this->signature_goal,
            'signature_count' => $this->signature_count,
            'status'          => $this->status,
            'deadline'        => $this->deadline?->toIso8601String(),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
