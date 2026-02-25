<?php

namespace App\Http\Resources\Risk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskIntelligenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'national_risk_score' => $this->resource['national_risk_score'],
            'risk_level'          => $this->resource['risk_level'],
            'trend'               => $this->resource['trend'],
            'active_categories'   => $this->resource['active_categories'],
            'signal_count'        => $this->resource['signal_count'],
            'last_updated'        => $this->resource['last_updated'],
            'confidence'          => $this->resource['confidence'],
            'executive_summary'   => $this->resource['executive_summary'],
            'volatility_index'    => $this->resource['volatility_index'],
            'acceleration'        => $this->resource['acceleration'],
            'stability_label'     => $this->resource['stability_label'],
            'fragility_index'          => $this->resource['fragility_index'],
            'fragility_label'          => $this->resource['fragility_label'],
            'risk_concentration_index' => $this->resource['risk_concentration_index'],
            'concentration_label'      => $this->resource['concentration_label'],
            'top_20_percent_share'     => $this->resource['top_20_percent_share'],
            'projected_risk_3m'        => $this->resource['projected_risk_3m'],
            'projection_confidence'    => $this->resource['projection_confidence'],
            'projection_trend'         => $this->resource['projection_trend'],
            'projected_risk_lower_3m'  => $this->resource['projected_risk_lower_3m'],
            'projected_risk_upper_3m'  => $this->resource['projected_risk_upper_3m'],
        ];
    }
}
