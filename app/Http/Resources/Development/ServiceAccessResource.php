<?php

namespace App\Http\Resources\Development;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceAccessResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'region_id' => $this->region_id,
            'year' => $this->year,
            'electricity_access_percent' => $this->electricity_access_percent,
            'safe_water_access_percent' => $this->safe_water_access_percent,
            'road_density_km_per_100km2' => $this->road_density_km_per_100km2,
            'paved_road_percent' => $this->paved_road_percent,
            'healthcare_facilities_total' => $this->healthcare_facilities_total,
            'healthcare_facilities_per_10000' => $this->healthcare_facilities_per_10000,
            'schools_total' => $this->schools_total,
            'schools_per_10000' => $this->schools_per_10000,
            'internet_penetration_percent' => $this->internet_penetration_percent,
            'mobile_network_coverage_percent' => $this->mobile_network_coverage_percent,
            'source_title' => $this->source_title,
            'data_version' => $this->data_version,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
