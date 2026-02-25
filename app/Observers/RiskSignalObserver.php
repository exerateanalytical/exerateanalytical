<?php

namespace App\Observers;

use App\Models\RiskSignal;
use Illuminate\Support\Facades\Cache;

class RiskSignalObserver
{
    public function created(RiskSignal $signal): void
    {
        Cache::forget("risk:intelligence:{$signal->country_id}");
    }

    public function updated(RiskSignal $signal): void
    {
        Cache::forget("risk:intelligence:{$signal->country_id}");
    }

    public function deleted(RiskSignal $signal): void
    {
        Cache::forget("risk:intelligence:{$signal->country_id}");
    }
}
