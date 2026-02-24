<?php

namespace App\Jobs;

use App\Models\GovernanceScore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WeeklyScoreStabilityCheckJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        Log::info('Running weekly score stability check');

        $currentYear = now()->year;
        $scores = GovernanceScore::where('year', $currentYear)
            ->whereNull('region_id')
            ->get();

        foreach ($scores as $score) {
            $previousScore = GovernanceScore::where('country_id', $score->country_id)
                ->where('year', $currentYear - 1)
                ->whereNull('region_id')
                ->latest('calculated_at')
                ->first();

            if ($previousScore) {
                $current = (float) $score->composite_score;
                $previous = (float) $previousScore->composite_score;
                if ($previous > 0) {
                    $change = abs(($current - $previous) / $previous) * 100;
                    if ($change > 15) {
                        Log::warning('Unusual score change detected', [
                            'country_id' => $score->country_id,
                            'change_percent' => $change,
                        ]);
                    }
                }
            }
        }

        Log::info('Weekly score stability check complete');
    }
}
