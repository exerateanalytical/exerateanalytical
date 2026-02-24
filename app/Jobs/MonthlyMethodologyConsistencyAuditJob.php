<?php

namespace App\Jobs;

use App\Models\BiasMonitoring;
use App\Models\Country;
use App\Models\GovernanceScore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonthlyMethodologyConsistencyAuditJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        Log::info('Running monthly methodology consistency audit');

        $countries = Country::active()->get();

        foreach ($countries as $country) {
            $scores = GovernanceScore::where('country_id', $country->id)
                ->whereNull('region_id')
                ->orderBy('year')
                ->get();

            if ($scores->count() < 2) continue;

            $composites = $scores->pluck('composite_score')->map(fn ($s) => (float) $s);
            $mean = $composites->avg();
            $variance = $composites->reduce(fn ($c, $v) => $c + pow($v - $mean, 2), 0) / $composites->count();

            BiasMonitoring::create([
                'country_id' => $country->id,
                'average_governance_score' => round($mean, 4),
                'score_variance' => round($variance, 4),
                'analysis_date' => now(),
            ]);

            Log::info('Bias monitoring record created', [
                'country_id' => $country->id,
                'mean' => $mean,
                'variance' => $variance,
            ]);
        }

        Log::info('Monthly methodology consistency audit complete');
    }
}
