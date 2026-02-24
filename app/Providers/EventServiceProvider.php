<?php

namespace App\Providers;

use App\Events\CountryCreated;
use App\Events\CountryUpdated;
use App\Events\GovernanceScoreRecalculated;
use App\Listeners\InvalidateCountryCacheListener;
use App\Listeners\TriggerGovernanceRecalculationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CountryCreated::class => [],

        CountryUpdated::class => [
            InvalidateCountryCacheListener::class,
            TriggerGovernanceRecalculationListener::class,
        ],

        GovernanceScoreRecalculated::class => [
            InvalidateCountryCacheListener::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
