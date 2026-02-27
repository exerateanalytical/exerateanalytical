<?php

namespace App\Http\Resources\Executive;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ControlTowerResource extends JsonResource
{
    /**
     * Transform the control tower service payload into the API response shape.
     *
     * The service already returns a fully-typed array; this resource provides
     * explicit key ordering and null-safety at the boundary.
     */
    public function toArray(Request $request): array
    {
        return [
            'hero'                 => $this->resource['hero']                 ?? null,
            'regions'              => $this->resource['regions']              ?? [],
            'policy_radar'         => $this->resource['policy_radar']         ?? [],
            'civic_momentum'       => $this->resource['civic_momentum']       ?? null,
            'contagion_forecast'   => $this->resource['contagion_forecast']   ?? null,
            'representation_index' => $this->resource['representation_index'] ?? [],
        ];
    }
}
