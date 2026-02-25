<?php

namespace App\Http\Resources\Risk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionalRiskDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'regional_risk_score' => $this->resource['regional_risk_score'],
            'risk_level'          => $this->resource['risk_level'],
            'trend'               => $this->resource['trend'],
            'domain_averages'     => $this->resource['domain_averages'],
            'signal_count'        => $this->resource['signal_count'],
            'volatility_index'    => $this->resource['volatility_index'],
            'acceleration'        => $this->resource['acceleration'],
            'stability_label'     => $this->resource['stability_label'],
            'fragility_index'                => $this->resource['fragility_index'],
            'fragility_label'                => $this->resource['fragility_label'],
            'risk_delta_from_national'       => $this->resource['risk_delta_from_national'],
            'fragility_delta_from_national'  => $this->resource['fragility_delta_from_national'],
            'divergence_label'               => $this->resource['divergence_label'],
            'projected_regional_risk_3m'       => $this->resource['projected_regional_risk_3m'],
            'projection_confidence'            => $this->resource['projection_confidence'],
            'projection_trend'                 => $this->resource['projection_trend'],
            'projected_regional_risk_lower_3m' => $this->resource['projected_regional_risk_lower_3m'],
            'projected_regional_risk_upper_3m' => $this->resource['projected_regional_risk_upper_3m'],
        ];
    }
}
