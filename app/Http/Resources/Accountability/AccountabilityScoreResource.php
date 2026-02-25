<?php

namespace App\Http\Resources\Accountability;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountabilityScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accountability_entity_id' => $this->accountability_entity_id,
            'budget_execution_score' => $this->budget_execution_score,
            'delivery_score' => $this->delivery_score,
            'service_impact_score' => $this->service_impact_score,
            'transparency_score' => $this->transparency_score,
            'composite_accountability_score' => $this->composite_accountability_score,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
        ];
    }
}
