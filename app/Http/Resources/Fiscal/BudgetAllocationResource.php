<?php

namespace App\Http\Resources\Fiscal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'region_id' => $this->region_id,
            'year' => $this->year,
            'sector_name' => $this->sector_name,
            'allocated_amount' => $this->allocated_amount,
            'executed_amount' => $this->executed_amount,
            'execution_rate' => $this->execution_rate,
            'delay_flag' => $this->delay_flag,
            'source_title' => $this->source_title,
            'source_url' => $this->source_url,
        ];
    }
}
