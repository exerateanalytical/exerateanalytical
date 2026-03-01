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

// AI Governance Advisor — refresh recommendations every 10 minutes
Schedule::command('governance:refresh-recommendations')->everyTenMinutes();

// CT-7 Governance Causality & Influence Map — run after advisor refresh
Schedule::command('governance:compute-influences')->everyTenMinutes();

// CT-8 Institutional & Actor Intelligence Layer — every 30 minutes
Schedule::command('governance:compute-institutional-influences')->everyThirtyMinutes();

// CT-9 Governance Trajectory Engine — every 15 minutes
Schedule::command('governance:compute-trajectories')->everyFifteenMinutes();

// CT-11 Civic Signal Prioritization Engine — every 15 minutes
Schedule::command('civic:compute-signal-priorities')->everyFifteenMinutes();
