<?php

namespace App\Http\Resources\Fiscal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevenueRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'year' => $this->year,
            'total_revenue' => $this->total_revenue,
            'tax_revenue' => $this->tax_revenue,
            'non_tax_revenue' => $this->non_tax_revenue,
            'grants' => $this->grants,
            'revenue_to_gdp_ratio' => $this->revenue_to_gdp_ratio,
            'source_title' => $this->source_title,
        ];
    }
}
