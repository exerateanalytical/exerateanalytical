<?php

namespace App\Observers;

use App\Events\CountryCreated;
use App\Events\CountryUpdated;
use App\Models\Country;
use Illuminate\Support\Facades\Log;

class CountryObserver
{
    public function created(Country $country): void
    {
        Log::info('Country record created', ['id' => $country->id]);
        CountryCreated::dispatch($country);
    }

    public function updated(Country $country): void
    {
        Log::info('Country record updated', ['id' => $country->id]);
        CountryUpdated::dispatch($country, $country->getOriginal());
    }

    public function deleted(Country $country): void
    {
        Log::warning('Country record soft-deleted', ['id' => $country->id]);
    }

    public function restored(Country $country): void
    {
        Log::info('Country record restored', ['id' => $country->id]);
    }
}
