<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'country_id'          => $this->resource['country_id'],
            'name'                => $this->resource['name'],
            'iso_code'            => $this->resource['iso_code'],
            'national_risk_score' => $this->resource['national_risk_score'],
            'risk_level'          => $this->resource['risk_level'],
            'trend'               => $this->resource['trend'],
            'signal_count'        => $this->resource['signal_count'],
            'confidence'          => $this->resource['confidence'],
        ];
    }
}
