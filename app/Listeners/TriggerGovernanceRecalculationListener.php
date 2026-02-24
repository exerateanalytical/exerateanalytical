<?php

namespace App\Listeners;

use App\Events\CountryUpdated;
use App\Jobs\RecalculateGovernanceScoreJob;

class TriggerGovernanceRecalculationListener
{
    public function handle(CountryUpdated $event): void
    {
        RecalculateGovernanceScoreJob::dispatch($event->country, now()->year);
    }
}
