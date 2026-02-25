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
}
