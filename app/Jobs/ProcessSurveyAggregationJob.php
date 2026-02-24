<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSurveyAggregationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly string $surveyId) {}

    public function handle(): void
    {
        Log::info('Processing survey aggregation', ['survey_id' => $this->surveyId]);
        // Survey aggregation logic will be implemented
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessSurveyAggregationJob failed permanently', [
            'survey_id' => $this->surveyId,
            'error' => $exception->getMessage(),
        ]);
    }
}
