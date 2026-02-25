<?php

use App\Models\Country;
use App\Models\FiscalRiskSignal;
use App\Models\RevenueRecord;
use App\Services\Fiscal\RevenueStabilityService;

beforeEach(function () {
    $this->service = new RevenueStabilityService();
    $this->country = Country::factory()->create();
});

it('does not trigger a signal when revenue drop is exactly 20 percent', function () {
    RevenueRecord::factory()->create([
        'country_id'    => $this->country->id,
        'year'          => 2022,
        'total_revenue' => 1000.00,
    ]);
    RevenueRecord::factory()->create([
        'country_id'    => $this->country->id,
        'year'          => 2023,
        'total_revenue' => 800.00,
    ]);

    $signals = $this->service->detectRevenueVolatility($this->country->id);

    expect($signals)->toBeEmpty();
    expect(FiscalRiskSignal::where('country_id', $this->country->id)
        ->where('risk_type', 'revenue_instability')->count())->toBe(0);
});

it('does not create duplicate signals when called multiple times', function () {
    RevenueRecord::factory()->create([
        'country_id'    => $this->country->id,
        'year'          => 2022,
        'total_revenue' => 1000.00,
    ]);
    RevenueRecord::factory()->create([
        'country_id'    => $this->country->id,
        'year'          => 2023,
        'total_revenue' => 600.00,
    ]);

    $this->service->detectRevenueVolatility($this->country->id);
    $this->service->detectRevenueVolatility($this->country->id);

    $count = FiscalRiskSignal::where('country_id', $this->country->id)
        ->where('year', 2023)
        ->where('risk_type', 'revenue_instability')
        ->count();

    expect($count)->toBe(1);
});
