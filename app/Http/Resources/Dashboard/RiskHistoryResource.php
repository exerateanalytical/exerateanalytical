<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'month'          => $this->resource['month'],
            'weighted_score' => $this->resource['weighted_score'],
        ];
    }
}
