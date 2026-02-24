<?php

namespace App\Http\Resources\Fiscal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'year' => $this->year,
            'total_debt' => $this->total_debt,
            'debt_to_gdp_ratio' => $this->debt_to_gdp_ratio,
            'external_debt' => $this->external_debt,
            'domestic_debt' => $this->domestic_debt,
            'debt_service_total' => $this->debt_service_total,
            'debt_service_ratio' => $this->debt_service_ratio,
            'interest_payments' => $this->interest_payments,
            'interest_as_budget_percent' => $this->interest_as_budget_percent,
            'risk_classification' => $this->risk_classification,
            'source_title' => $this->source_title,
            'source_url' => $this->source_url,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
