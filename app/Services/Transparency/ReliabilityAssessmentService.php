<?php

namespace App\Services\Transparency;

use App\Models\Indicator;
use App\Models\IndicatorReliabilityScore;
use Illuminate\Support\Facades\Log;

class ReliabilityAssessmentService
{
    public function updateReliability(string $indicatorId): IndicatorReliabilityScore
    {
        $indicator = Indicator::findOrFail($indicatorId);

        $valueCount = $indicator->values()->count();
        $reliabilityLevel = $indicator->reliability_level;

        $verificationStatus = $valueCount >= 3 ? 'multi_source_confirmed' : 'single_source';

        $score = IndicatorReliabilityScore::updateOrCreate(
            ['indicator_id' => $indicatorId],
            [
                'reliability_level' => $reliabilityLevel,
                'reporting_lag_months' => $indicator->reporting_lag_months,
                'verification_status' => $verificationStatus,
                'last_reviewed_at' => now(),
            ]
        );

        Log::info('Indicator reliability updated', [
            'indicator_id' => $indicatorId,
            'level' => $reliabilityLevel,
            'verification' => $verificationStatus,
        ]);

        return $score;
    }
}
