<?php

namespace App\Providers;

use App\Models\Country;
use App\Models\Institution;
use App\Models\Region;
use App\Observers\CountryObserver;
use App\Observers\InstitutionObserver;
use App\Policies\CountryPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\RegionPolicy;
use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Repositories\Contracts\InstitutionRepositoryInterface;
use App\Repositories\Contracts\RegionRepositoryInterface;
use App\Repositories\Eloquent\CountryRepository;
use App\Repositories\Eloquent\InstitutionRepository;
use App\Repositories\Eloquent\RegionRepository;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings.
     */
    public function register(): void
    {
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(RegionRepositoryInterface::class, RegionRepository::class);
        $this->app->bind(InstitutionRepositoryInterface::class, InstitutionRepository::class);

        if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        // Register model observers
        Country::observe(CountryObserver::class);
        Institution::observe(InstitutionObserver::class);

        // Register policies
        Gate::policy(Country::class, CountryPolicy::class);
        Gate::policy(Region::class, RegionPolicy::class);
        Gate::policy(Institution::class, InstitutionPolicy::class);

        // API rate limiting
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
