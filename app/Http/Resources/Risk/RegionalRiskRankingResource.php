<?php

namespace App\Http\Resources\Risk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionalRiskRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'region_id'           => $this->resource['region_id'],
            'name'                => $this->resource['name'],
            'regional_risk_score' => $this->resource['regional_risk_score'],
            'risk_level'          => $this->resource['risk_level'],
            'trend'               => $this->resource['trend'],
            'signal_count'        => $this->resource['signal_count'],
        ];
    }
}
