<?php

use App\Models\Country;
use App\Models\FiscalRiskSignal;
use App\Models\RiskSignal;
use App\Services\Risk\RiskIntelligenceService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = new RiskIntelligenceService();
    $this->country = Country::factory()->create();
});

// ─── Domain weighting ─────────────────────────────────────────────────────────

it('computes national risk score using domain weights', function () {
    $now = Carbon::now();

    // Governance: high = 75, weight 0.4  → contribution 30.00
    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'governance_risk',
        'module'       => 'Governance',
        'severity'     => 'high',
        'triggered_at' => $now->copy()->subDays(5),
    ]);

    // Fiscal: low = 25, weight 0.35  → contribution 8.75
    FiscalRiskSignal::create([
        'country_id'   => $this->country->id,
        'year'         => $now->year,
        'risk_type'    => 'budget_execution',
        'severity'     => 'low',
        'triggered_at' => $now->copy()->subDays(5),
    ]);

    // Accountability: moderate = 50, weight 0.25  → contribution 12.50
    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'accountability_risk',
        'module'       => 'Accountability',
        'severity'     => 'moderate',
        'triggered_at' => $now->copy()->subDays(5),
    ]);

    $summary = $this->service->getNationalRiskSummary($this->country->id);

    // (75 × 0.4) + (25 × 0.35) + (50 × 0.25) = 30 + 8.75 + 12.5 = 51.25
    expect($summary['national_risk_score'])->toBe(51.25);
});

it('treats a domain with no signals as contributing 0 without renormalising weights', function () {
    $now = Carbon::now();

    // Only a Governance signal; fiscal and accountability domains are empty.
    // Score = (75 × 0.4) + (0 × 0.35) + (0 × 0.25) = 30.00
    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'governance_risk',
        'module'       => 'Governance',
        'severity'     => 'high',
        'triggered_at' => $now->copy()->subDays(5),
    ]);

    $summary = $this->service->getNationalRiskSummary($this->country->id);

    expect($summary['national_risk_score'])->toBe(30.0);
});

// ─── Trend detection ──────────────────────────────────────────────────────────

it('detects deteriorating trend when recent weighted score exceeds baseline by more than 5', function () {
    $now = Carbon::now();

    // Baseline (60 days ago): low → normalized 25, weighted 25 × 0.4 = 10
    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'governance_risk',
        'module'       => 'Governance',
        'severity'     => 'low',
        'triggered_at' => $now->copy()->subDays(60),
    ]);

    // Recent (5 days ago): critical → normalized 100, weighted 100 × 0.4 = 40
    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'governance_risk',
        'module'       => 'Governance',
        'severity'     => 'critical',
        'triggered_at' => $now->copy()->subDays(5),
    ]);

    $summary = $this->service->getNationalRiskSummary($this->country->id);

    // recentWeighted=40 > baselineWeighted=10 + 5 → deteriorating
    expect($summary['trend'])->toBe('deteriorating');
});

it('detects improving trend when recent weighted score is more than 5 below baseline', function () {
    $now = Carbon::now();

    // Baseline (60 days ago): critical → normalized 100, weighted 100 × 0.4 = 40
    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'governance_risk',
        'module'       => 'Governance',
        'severity'     => 'critical',
        'triggered_at' => $now->copy()->subDays(60),
    ]);

    // Recent (5 days ago): low → normalized 25, weighted 25 × 0.4 = 10
    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'governance_risk',
        'module'       => 'Governance',
        'severity'     => 'low',
        'triggered_at' => $now->copy()->subDays(5),
    ]);

    $summary = $this->service->getNationalRiskSummary($this->country->id);

    // recentWeighted=10 < baselineWeighted=40 - 5 → improving
    expect($summary['trend'])->toBe('improving');
});

it('returns stable trend when weighted scores are within the 5-point threshold', function () {
    $now = Carbon::now();

    // Baseline and recent are both moderate (50); difference = 0 → stable
    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'governance_risk',
        'module'       => 'Governance',
        'severity'     => 'moderate',
        'triggered_at' => $now->copy()->subDays(60),
    ]);

    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'governance_risk',
        'module'       => 'Governance',
        'severity'     => 'moderate',
        'triggered_at' => $now->copy()->subDays(5),
    ]);

    $summary = $this->service->getNationalRiskSummary($this->country->id);

    expect($summary['trend'])->toBe('stable');
});

// ─── Empty baseline case ──────────────────────────────────────────────────────

it('returns stable trend when baseline window has no signals', function () {
    $now = Carbon::now();

    // Only a recent signal; the 31–90 day baseline window is empty
    RiskSignal::create([
        'country_id'   => $this->country->id,
        'signal_type'  => 'governance_risk',
        'module'       => 'Governance',
        'severity'     => 'high',
        'triggered_at' => $now->copy()->subDays(5),
    ]);

    $summary = $this->service->getNationalRiskSummary($this->country->id);

    expect($summary['trend'])->toBe('stable');
});

it('returns stable trend and low confidence when no signals exist at all', function () {
    $summary = $this->service->getNationalRiskSummary($this->country->id);

    expect($summary['trend'])->toBe('stable');
    expect($summary['national_risk_score'])->toBe(0.0);
    expect($summary['confidence'])->toBe('low');
});

// ─── Confidence flag ──────────────────────────────────────────────────────────

it('sets confidence to low when fewer than 3 signals exist in the 12-month window', function () {
    $now = Carbon::now();

    foreach (['moderate', 'low'] as $severity) {
        RiskSignal::create([
            'country_id'   => $this->country->id,
            'signal_type'  => 'governance_risk',
            'module'       => 'Governance',
            'severity'     => $severity,
            'triggered_at' => $now->copy()->subDays(10),
        ]);
    }

    // 2 signals < 3 → confidence low
    expect($this->service->getNationalRiskSummary($this->country->id)['confidence'])->toBe('low');
});

it('sets confidence to normal when exactly 3 signals exist in the 12-month window', function () {
    $now = Carbon::now();

    foreach (['moderate', 'high', 'low'] as $severity) {
        RiskSignal::create([
            'country_id'   => $this->country->id,
            'signal_type'  => 'governance_risk',
            'module'       => 'Governance',
            'severity'     => $severity,
            'triggered_at' => $now->copy()->subDays(10),
        ]);
    }

    // exactly 3 signals → confidence normal
    expect($this->service->getNationalRiskSummary($this->country->id)['confidence'])->toBe('normal');
});

it('excludes signals older than 12 months from signal count and confidence calculation', function () {
    $now = Carbon::now();

    // 3 signals inside the window
    foreach (range(1, 3) as $_) {
        RiskSignal::create([
            'country_id'   => $this->country->id,
            'signal_type'  => 'governance_risk',
            'module'       => 'Governance',
            'severity'     => 'moderate',
            'triggered_at' => $now->copy()->subDays(10),
        ]);
    }

    // 5 signals outside the window (> 12 months ago) — must not be counted
    foreach (range(1, 5) as $_) {
        RiskSignal::create([
            'country_id'   => $this->country->id,
            'signal_type'  => 'governance_risk',
            'module'       => 'Governance',
            'severity'     => 'critical',
            'triggered_at' => $now->copy()->subMonths(13),
        ]);
    }

    $summary = $this->service->getNationalRiskSummary($this->country->id);

    expect($summary['signal_count'])->toBe(3);
    expect($summary['confidence'])->toBe('normal');
});
