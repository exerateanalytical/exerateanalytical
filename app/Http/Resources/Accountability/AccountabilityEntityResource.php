<?php

namespace App\Http\Resources\Accountability;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountabilityEntityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'region_id' => $this->region_id,
            'year' => $this->year,
            'mandate_area' => $this->mandate_area,
            'legal_basis_reference' => $this->legal_basis_reference,
            'institution' => $this->whenLoaded('institution', fn () => [
                'id' => $this->institution->id,
                'name' => $this->institution->name,
                'type' => $this->institution->type,
            ]),
            'score' => $this->whenLoaded('score', fn () => $this->score ? [
                'budget_execution_score' => $this->score->budget_execution_score,
                'delivery_score' => $this->score->delivery_score,
                'service_impact_score' => $this->score->service_impact_score,
                'transparency_score' => $this->score->transparency_score,
                'composite_accountability_score' => $this->score->composite_accountability_score,
                'calculated_at' => $this->score->calculated_at?->toIso8601String(),
            ] : null),
        ];
    }
}
