<?php

namespace App\Http\Resources\Governance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GovernanceScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'region_id' => $this->region_id,
            'year' => $this->year,
            'composite_score' => $this->composite_score,
            'pillar_scores' => $this->pillar_scores,
            'version' => $this->version,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
        ];
    }
}
