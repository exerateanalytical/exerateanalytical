<?php

namespace App\Observers;

use App\Models\Region;
use Illuminate\Support\Facades\Log;

class RegionObserver
{
    public function created(Region $region): void
    {
        Log::info('Region record created', ['id' => $region->id, 'country_id' => $region->country_id]);
    }

    public function updated(Region $region): void
    {
        Log::info('Region record updated', ['id' => $region->id, 'country_id' => $region->country_id]);
    }

    public function deleted(Region $region): void
    {
        Log::warning('Region record soft-deleted', ['id' => $region->id, 'country_id' => $region->country_id]);
    }

    public function restored(Region $region): void
    {
        Log::info('Region record restored', ['id' => $region->id, 'country_id' => $region->country_id]);
    }
}
