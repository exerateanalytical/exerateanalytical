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
        ];
    }
}
