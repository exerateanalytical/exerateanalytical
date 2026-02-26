<?php

use App\Jobs\DailyDataIntegrityCheckJob;
use App\Jobs\MonthlyMethodologyConsistencyAuditJob;
use App\Jobs\WeeklyScoreStabilityCheckJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automated QA and audit jobs
Schedule::job(new DailyDataIntegrityCheckJob)->daily();
Schedule::job(new WeeklyScoreStabilityCheckJob)->weekly();
Schedule::job(new MonthlyMethodologyConsistencyAuditJob)->monthly();

// Risk alert escalation
Schedule::command('risk:escalate')->everyFiveMinutes();

// Federation snapshot pipeline
Schedule::command('risk:publish-regional-snapshot')->everyFiveMinutes();
Schedule::command('risk:compute-federation-global')->everyFiveMinutes();
