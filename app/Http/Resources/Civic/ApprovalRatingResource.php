<?php

namespace App\Http\Resources\Civic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'region_id' => $this->region_id,
            'year' => $this->year,
            'approve_percent' => $this->approve_percent,
            'disapprove_percent' => $this->disapprove_percent,
            'neutral_percent' => $this->neutral_percent,
            'sample_size' => $this->sample_size,
            'margin_of_error' => $this->margin_of_error,
            'confidence_interval' => $this->confidence_interval,
            'rolling_average_90_day' => $this->rolling_average_90_day,
            'calculated_at' => $this->calculated_at?->toIso8601String(),
        ];
    }
}
