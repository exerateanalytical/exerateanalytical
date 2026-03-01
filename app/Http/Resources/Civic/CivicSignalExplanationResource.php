<?php

namespace App\Http\Resources\Civic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CivicSignalExplanationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'signal_priority_id'   => $this->signal_priority_id,
            'primary_driver'       => $this->primary_driver,
            'driver_breakdown'     => $this->driver_breakdown,
            'affected_regions'     => $this->affected_regions,
            'trajectory_direction' => $this->trajectory_direction,
            'projected_risk_level' => $this->projected_risk_level,
            'explanation_summary'  => $this->explanation_summary,
            'created_at'           => $this->created_at?->toIso8601String(),
        ];
    }
}
