<?php

namespace App\Services\Civic;

use App\Models\ApprovalRating;
use App\Models\RiskSignal;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Log;

class SurveyAggregationService
{
    private const MIN_SAMPLE_SIZE = 100;
    private const APPROVAL_DROP_THRESHOLD = 20.0;

    public function calculateApprovalRating(string $countryId, ?string $regionId, int $year): ApprovalRating
    {
        $query = SurveyResponse::whereHas('survey', fn ($q) => $q->where('country_id', $countryId)->where('type', 'approval'));

        if ($regionId) {
            $query->where('region_id', $regionId);
        }

        $responses = $query->get();
        $n = $responses->count();

        if ($n < self::MIN_SAMPLE_SIZE) {
            Log::warning('Low sample size for approval rating', [
                'country_id' => $countryId,
                'sample_size' => $n,
            ]);
        }

        $approveCount = $responses->filter(fn ($r) => ($r->response_data['approval'] ?? null) === 'approve')->count();
        $disapproveCount = $responses->filter(fn ($r) => ($r->response_data['approval'] ?? null) === 'disapprove')->count();
        $neutralCount = $n - $approveCount - $disapproveCount;

        $approvePercent = $n > 0 ? round(($approveCount / $n) * 100, 2) : 0;
        $disapprovePercent = $n > 0 ? round(($disapproveCount / $n) * 100, 2) : 0;
        $neutralPercent = $n > 0 ? round(($neutralCount / $n) * 100, 2) : 0;

        $p = $approvePercent / 100;
        $marginOfError = $n > 0 ? round(1.96 * sqrt($p * (1 - $p) / $n) * 100, 2) : null;

        $rating = ApprovalRating::updateOrCreate(
            ['country_id' => $countryId, 'region_id' => $regionId, 'year' => $year],
            [
                'approve_percent' => $approvePercent,
                'disapprove_percent' => $disapprovePercent,
                'neutral_percent' => $neutralPercent,
                'sample_size' => $n,
                'margin_of_error' => $marginOfError,
                'confidence_interval' => '95%',
                'calculated_at' => now(),
            ]
        );

        $this->updateRollingAverage($rating);
        $this->checkApprovalDropAlert($countryId, $regionId, $year, $approvePercent);

        return $rating;
    }

    private function updateRollingAverage(ApprovalRating $rating): void
    {
        $recent = ApprovalRating::where('country_id', $rating->country_id)
            ->where('region_id', $rating->region_id)
            ->orderBy('year', 'desc')
            ->limit(3)
            ->pluck('approve_percent');

        if ($recent->isNotEmpty()) {
            $rating->rolling_average_90_day = round($recent->avg(), 2);
            $rating->save();
        }
    }

    private function checkApprovalDropAlert(string $countryId, ?string $regionId, int $year, float $currentApproval): void
    {
        $sixMonthsAgo = ApprovalRating::where('country_id', $countryId)
            ->where('region_id', $regionId)
            ->where('year', '<', $year)
            ->orderBy('year', 'desc')
            ->first();

        if (!$sixMonthsAgo) return;

        $prevApproval = (float) $sixMonthsAgo->approve_percent;
        if ($prevApproval > 0) {
            $change = (($currentApproval - $prevApproval) / $prevApproval) * 100;
            if ($change < -self::APPROVAL_DROP_THRESHOLD) {
                RiskSignal::create([
                    'country_id' => $countryId,
                    'signal_type' => 'civic_approval_drop',
                    'severity' => 'high',
                    'module' => 'Civic',
                    'description' => "Approval rating dropped by " . round(abs($change), 2) . "%.",
                    'triggered_at' => now(),
                ]);
            }
        }
    }

    public function calculateCronbachAlpha(string $surveyId): float
    {
        $responses = SurveyResponse::where('survey_id', $surveyId)->get();
        if ($responses->count() < 2) return 0.0;

        $items = $responses->map(fn ($r) => array_values((array) $r->response_data));
        $k = count($items->first() ?? []);
        if ($k < 2) return 0.0;

        $itemVariances = [];
        for ($i = 0; $i < $k; $i++) {
            $vals = $items->map(fn ($r) => (float) ($r[$i] ?? 0));
            $mean = $vals->avg();
            $variance = $vals->reduce(fn ($c, $v) => $c + pow($v - $mean, 2), 0) / $vals->count();
            $itemVariances[] = $variance;
        }

        $totalScores = $items->map(fn ($r) => array_sum($r));
        $totalMean = $totalScores->avg();
        $totalVariance = $totalScores->reduce(fn ($c, $v) => $c + pow($v - $totalMean, 2), 0) / $totalScores->count();

        if ($totalVariance == 0) return 0.0;

        $alpha = ($k / ($k - 1)) * (1 - array_sum($itemVariances) / $totalVariance);

        return round(max(0, min(1, $alpha)), 4);
    }
}
