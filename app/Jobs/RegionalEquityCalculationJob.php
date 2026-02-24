<?php

namespace App\Jobs;

use App\Models\Country;
use App\Services\Development\RegionalEquityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RegionalEquityCalculationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $countryId,
        public readonly int $year
    ) {}

    public function handle(RegionalEquityService $service): void
    {
        Log::info('Running regional equity calculation', [
            'country_id' => $this->countryId,
            'year' => $this->year,
        ]);

        $service->calculateEquityScore($this->countryId, $this->year);

        Log::info('Regional equity calculation complete', [
            'country_id' => $this->countryId,
            'year' => $this->year,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RegionalEquityCalculationJob failed', [
            'country_id' => $this->countryId,
            'year' => $this->year,
            'error' => $exception->getMessage(),
        ]);
    }
}
