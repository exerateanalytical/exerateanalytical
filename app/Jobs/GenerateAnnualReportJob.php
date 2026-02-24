<?php

namespace App\Jobs;

use App\Models\Country;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAnnualReportJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    public function __construct(public readonly Country $country, public readonly int $year) {}

    public function handle(): void
    {
        Log::info('Generating annual report', [
            'country_id' => $this->country->id,
            'year' => $this->year,
        ]);

        // Report generation logic will be implemented
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical('GenerateAnnualReportJob failed permanently', [
            'country_id' => $this->country->id,
            'year' => $this->year,
            'error' => $exception->getMessage(),
        ]);
    }
}
