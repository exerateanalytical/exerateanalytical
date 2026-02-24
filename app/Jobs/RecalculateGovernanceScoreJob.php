<?php

namespace App\Jobs;

use App\Exceptions\ScoreComputationException;
use App\Models\Country;
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

    public function __construct(public readonly Country $country, public readonly int $year) {}

    public function handle(): void
    {
        Log::info('Recalculating governance score', [
            'country_id' => $this->country->id,
            'year' => $this->year,
        ]);

        try {
            // Score computation logic will be implemented in the GovernanceService
        } catch (\Throwable $e) {
            Log::error('Governance score computation failed', [
                'country_id' => $this->country->id,
                'year' => $this->year,
                'error' => $e->getMessage(),
            ]);

            throw new ScoreComputationException(
                "Failed to compute governance score for country [{$this->country->id}] year [{$this->year}]: {$e->getMessage()}"
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('RecalculateGovernanceScoreJob failed permanently', [
            'country_id' => $this->country->id,
            'year' => $this->year,
            'error' => $exception->getMessage(),
        ]);
    }
}
