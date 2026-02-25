<?php

namespace App\Http\Resources\Fiscal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FiscalRiskSignalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'country_id'   => $this->country_id,
            'year'         => $this->year,
            'risk_type'    => $this->risk_type,
            'severity'     => $this->severity,
            'description'  => $this->description,
            'triggered_at' => $this->triggered_at?->toIso8601String(),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
