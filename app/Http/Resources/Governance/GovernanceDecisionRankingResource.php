<?php

namespace App\Http\Resources\Governance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GovernanceDecisionRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                            => $this->id,
            'recommendation_id'             => $this->recommendation_id,
            'scenario_type'                 => $this->scenario_type,
            'projected_stability_delta'     => $this->projected_stability_delta,
            'projected_trust_delta'         => $this->projected_trust_delta,
            'projected_cascade_delta'       => $this->projected_cascade_delta,
            'projected_participation_delta' => $this->projected_participation_delta,
            'decision_score'                => $this->decision_score,
            'rank_position'                 => $this->rank_position,
            'created_at'                    => $this->created_at?->toIso8601String(),
        ];
    }
}
