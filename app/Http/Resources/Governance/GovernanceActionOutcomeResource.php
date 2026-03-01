<?php

namespace App\Http\Resources\Governance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GovernanceActionOutcomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'governance_action_id' => $this->governance_action_id,
            'observation_window'   => $this->observation_window,
            'stability_delta'      => $this->stability_delta,
            'trust_delta'          => $this->trust_delta,
            'participation_delta'  => $this->participation_delta,
            'cascade_delta'        => $this->cascade_delta,
            'effectiveness_score'  => $this->effectiveness_score,
            'measured_at'          => $this->measured_at?->toIso8601String(),
            'created_at'           => $this->created_at?->toIso8601String(),
        ];
    }
}
