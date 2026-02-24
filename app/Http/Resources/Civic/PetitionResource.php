<?php

namespace App\Http\Resources\Civic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'status' => $this->status,
            'signature_count' => $this->signatures()->count(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
