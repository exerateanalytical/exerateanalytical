<?php

namespace App\Jobs;

use App\Events\GovernanceScoreRecalculated;
use App\Models\Country;
use App\Services\Governance\GovernanceIndexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateGovernanceScoreJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly string $countryId, public readonly int $year) {}

    public function handle(GovernanceIndexService $service): void
    {
        Log::info('Recalculating governance score', [
            'country_id' => $this->countryId,
            'year' => $this->year,
        ]);

        // calculateCompositeScore() dispatches GovernanceRiskEvaluationJob internally
        // when regionId is null. Orchestration ownership moves to job layer in Batch 3.
        $score = $service->calculateCompositeScore($this->countryId, null, $this->year);

        $country = Country::findOrFail($this->countryId);
        GovernanceScoreRecalculated::dispatch($country, $this->year, (float) $score->composite_score);
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('RecalculateGovernanceScoreJob failed permanently', [
            'country_id' => $this->countryId,
            'year' => $this->year,
            'error' => $exception->getMessage(),
        ]);
    }
}
