<?php

namespace App\Jobs;

use App\Exceptions\ScoreComputationException;
use App\Models\Region;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateRegionalScoreJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly Region $region, public readonly int $year) {}

    public function handle(): void
    {
        Log::info('Recalculating regional score', [
            'region_id' => $this->region->id,
            'year' => $this->year,
        ]);

        try {
            // Regional score computation logic will be implemented
        } catch (\Throwable $e) {
            Log::error('Regional score computation failed', [
                'region_id' => $this->region->id,
                'year' => $this->year,
                'error' => $e->getMessage(),
            ]);

            throw new ScoreComputationException(
                "Failed to compute regional score for region [{$this->region->id}]: {$e->getMessage()}"
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('RecalculateRegionalScoreJob failed permanently', [
            'region_id' => $this->region->id,
            'year' => $this->year,
            'error' => $exception->getMessage(),
        ]);
    }
}
