<?php

namespace App\Http\Resources\Country;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'iso_code' => $this->iso_code,
            'continent_region' => $this->continent_region,
            'risk_tier' => $this->risk_tier->value,
            'risk_tier_label' => $this->risk_tier->label(),
            'risk_tier_color' => $this->risk_tier->color(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
