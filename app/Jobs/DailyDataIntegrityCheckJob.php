<?php

namespace App\Jobs;

use App\Models\Country;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DailyDataIntegrityCheckJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        Log::info('Running daily data integrity check');

        // Check for indicators with missing values in current year
        $currentYear = now()->year;
        $indicators = Indicator::active()->get();

        foreach ($indicators as $indicator) {
            $hasValues = IndicatorValue::where('indicator_id', $indicator->id)
                ->where('year', $currentYear)
                ->exists();

            if (!$hasValues) {
                Log::warning('Missing indicator values for current year', [
                    'indicator_id' => $indicator->id,
                    'indicator_name' => $indicator->name,
                    'year' => $currentYear,
                ]);
            }
        }

        Log::info('Daily data integrity check complete');
    }
}
