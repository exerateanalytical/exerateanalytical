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
        ];
    }
}
