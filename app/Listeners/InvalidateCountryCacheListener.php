<?php

namespace App\Listeners;

use App\Events\CountryUpdated;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class InvalidateCountryCacheListener
{
    public function handle(CountryUpdated $event): void
    {
        Cache::forget("country:{$event->country->id}");
        Cache::forget('countries:active');

        Log::info('Country cache invalidated', ['country_id' => $event->country->id]);
    }
}
