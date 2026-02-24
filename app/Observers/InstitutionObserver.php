<?php

namespace App\Observers;

use App\Models\Institution;
use Illuminate\Support\Facades\Log;

class InstitutionObserver
{
    public function created(Institution $institution): void
    {
        Log::info('Institution record created', ['id' => $institution->id, 'country_id' => $institution->country_id]);
    }

    public function updated(Institution $institution): void
    {
        Log::info('Institution record updated', ['id' => $institution->id]);
    }

    public function deleted(Institution $institution): void
    {
        Log::warning('Institution record soft-deleted', ['id' => $institution->id]);
    }
}
