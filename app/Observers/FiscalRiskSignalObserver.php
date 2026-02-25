<?php

namespace App\Observers;

use App\Models\FiscalRiskSignal;
use Illuminate\Support\Facades\Cache;

class FiscalRiskSignalObserver
{
    public function created(FiscalRiskSignal $signal): void
    {
        Cache::forget("risk:intelligence:{$signal->country_id}");
    }
}
