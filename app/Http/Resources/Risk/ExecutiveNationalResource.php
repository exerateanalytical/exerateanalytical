<?php

namespace App\Http\Resources\Risk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExecutiveNationalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $summary  = $this->resource['summary'];
        $systemic = $this->resource['systemic_entry'];

        return [
            'country_id'       => $this->resource['country_id'],
            'national_summary' => [
                'national_risk_score'   => $summary['national_risk_score'],
                'risk_level'            => $summary['risk_level'],
                'trend'                 => $summary['trend'],
                'confidence'            => $summary['confidence'],
                'signal_count'          => $summary['signal_count'],
                'last_updated'          => $summary['last_updated'],
                'executive_summary'     => $summary['executive_summary'],
                'volatility_index'      => $summary['volatility_index'],
                'acceleration'          => $summary['acceleration'],
                'stability_label'       => $summary['stability_label'],
                'fragility_index'       => $summary['fragility_index'],
                'fragility_label'       => $summary['fragility_label'],
                'projected_risk_3m'     => $summary['projected_risk_3m'],
                'projection_confidence' => $summary['projection_confidence'],
                'projection_trend'      => $summary['projection_trend'],
                'regime_shift_detected' => $summary['regime_shift_detected'],
                'regime_shift_type'     => $summary['regime_shift_type'],
                'regime_shift_severity' => $summary['regime_shift_severity'],
            ],
            'systemic_profile' => [
                'stress_amplification_score' => $systemic['stress_amplification_score'] ?? null,
                'systemic_importance_score'  => $systemic['systemic_importance_score'] ?? null,
                'outgoing_exposure_weight'   => $systemic['outgoing_exposure_weight'] ?? null,
                'ranking_position'           => $this->resource['ranking_position'],
                'total_countries'            => $this->resource['total_countries'] ?? null,
            ],
        ];
    }
}
