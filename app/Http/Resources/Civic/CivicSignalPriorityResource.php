<?php

namespace App\Http\Resources\Civic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CivicSignalPriorityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'signal_type'            => $this->signal_type,
            'signal_id'              => $this->signal_id,
            'signal_title'           => $this->signal_title,
            'signal_region'          => $this->signal_region,
            'priority_score'         => (float) $this->priority_score,
            'participation_velocity' => (float) $this->participation_velocity,
            'cross_region_factor'    => (float) $this->cross_region_factor,
            'trust_impact'           => (float) $this->trust_impact,
            'calculated_at'          => $this->calculated_at?->toIso8601String(),
        ];
    }
}
