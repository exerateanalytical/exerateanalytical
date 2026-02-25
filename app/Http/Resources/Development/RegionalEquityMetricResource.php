<?php

namespace App\Http\Resources\Development;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionalEquityMetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'country_id'                  => $this->country_id,
            'year'                        => $this->year,
            'equity_score'                => $this->equity_score,
            'electricity_variance'        => $this->electricity_variance,
            'water_variance'              => $this->water_variance,
            'road_density_variance'       => $this->road_density_variance,
            'healthcare_density_variance' => $this->healthcare_density_variance,
            'education_density_variance'  => $this->education_density_variance,
            'digital_access_variance'     => $this->digital_access_variance,
            'calculated_at'               => $this->calculated_at?->toIso8601String(),
        ];
    }
}
