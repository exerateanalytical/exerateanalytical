<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskDriversResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'country_id'     => $this->resource['country_id'],
            'governance'     => $this->resource['governance'],
            'fiscal'         => $this->resource['fiscal'],
            'accountability' => $this->resource['accountability'],
            'weights'        => $this->resource['weights'],
        ];
    }
}
