<?php

use App\Models\Country;
use App\Models\Region;
use App\Models\RegionalEquityMetric;
use App\Models\ServiceAccessRecord;
use App\Models\User;
use App\Services\Development\RegionalEquityService;

beforeEach(function () {
    $this->service = new RegionalEquityService();
    $this->country = Country::factory()->create();
});

it('calculateVariance returns 0.0 when fewer than 2 regions have data', function () {
    // Only one regional record
    $region = Region::factory()->create(['country_id' => $this->country->id]);
    ServiceAccessRecord::create([
        'country_id' => $this->country->id,
        'region_id' => $region->id,
        'year' => 2023,
        'electricity_access_percent' => 80.00,
    ]);

    $variance = $this->service->calculateVariance($this->country->id, 2023, 'electricity_access_percent');

    expect($variance)->toBe(0.0);
});

it('calculateVariance returns 0.0 when no regional records exist', function () {
    $variance = $this->service->calculateVariance($this->country->id, 2023, 'electricity_access_percent');

    expect($variance)->toBe(0.0);
});

it('calculateVariance returns the correct variance for known values', function () {
    $regionA = Region::factory()->create(['country_id' => $this->country->id]);
    $regionB = Region::factory()->create(['country_id' => $this->country->id]);
    $regionC = Region::factory()->create(['country_id' => $this->country->id]);

    // Values: 40, 60, 80 => mean = 60
    // variance = ((40-60)^2 + (60-60)^2 + (80-60)^2) / 3 = (400 + 0 + 400) / 3 ≈ 266.6667
    ServiceAccessRecord::create(['country_id' => $this->country->id, 'region_id' => $regionA->id, 'year' => 2023, 'electricity_access_percent' => 40.00]);
    ServiceAccessRecord::create(['country_id' => $this->country->id, 'region_id' => $regionB->id, 'year' => 2023, 'electricity_access_percent' => 60.00]);
    ServiceAccessRecord::create(['country_id' => $this->country->id, 'region_id' => $regionC->id, 'year' => 2023, 'electricity_access_percent' => 80.00]);

    $variance = $this->service->calculateVariance($this->country->id, 2023, 'electricity_access_percent');

    // Expected: round(266.6667, 4) = 266.6667
    expect($variance)->toBeGreaterThan(266.0);
    expect($variance)->toBeLessThan(267.0);
});

it('calculateEquityScore creates or updates a RegionalEquityMetric record', function () {
    // No regional records means all variances = 0 and equity score = 100
    $metric = $this->service->calculateEquityScore($this->country->id, 2023);

    expect($metric)->toBeInstanceOf(RegionalEquityMetric::class);
    expect($metric->country_id)->toBe($this->country->id);
    expect($metric->year)->toBe(2023);

    $dbRecord = RegionalEquityMetric::where('country_id', $this->country->id)
        ->where('year', 2023)
        ->first();

    expect($dbRecord)->not->toBeNull();
});

it('calculateEquityScore updates the record on a second call', function () {
    $this->service->calculateEquityScore($this->country->id, 2023);
    $this->service->calculateEquityScore($this->country->id, 2023);

    $count = RegionalEquityMetric::where('country_id', $this->country->id)
        ->where('year', 2023)
        ->count();

    // updateOrCreate should produce only one record
    expect($count)->toBe(1);
});

it('equity score is between 0 and 100', function () {
    $regionA = Region::factory()->create(['country_id' => $this->country->id]);
    $regionB = Region::factory()->create(['country_id' => $this->country->id]);

    // Extreme disparity: one region has near-zero access, the other near-full
    ServiceAccessRecord::create([
        'country_id' => $this->country->id,
        'region_id' => $regionA->id,
        'year' => 2023,
        'electricity_access_percent' => 5.00,
        'safe_water_access_percent' => 5.00,
        'internet_penetration_percent' => 2.00,
    ]);
    ServiceAccessRecord::create([
        'country_id' => $this->country->id,
        'region_id' => $regionB->id,
        'year' => 2023,
        'electricity_access_percent' => 99.00,
        'safe_water_access_percent' => 97.00,
        'internet_penetration_percent' => 95.00,
    ]);

    $metric = $this->service->calculateEquityScore($this->country->id, 2023);

    expect((float) $metric->equity_score)->toBeGreaterThanOrEqual(0.0);
    expect((float) $metric->equity_score)->toBeLessThanOrEqual(100.0);
});

it('creates a risk signal when equity score deteriorates beyond the threshold', function () {
    // Establish a high previous equity score (year 2022)
    RegionalEquityMetric::create([
        'country_id'    => $this->country->id,
        'year'          => 2022,
        'equity_score'  => 100.00,
        'calculated_at' => now(),
    ]);

    // Two regions with extreme electricity disparity for year 2023:
    // values: 5, 95 → mean=50, variance=2025
    // avgNormalizedVariance = 1/6 ≈ 0.1667 → equity ≈ 83.33
    // change = (83.33 - 100) / 100 * 100 ≈ -16.67% → below -15% threshold → signal fires
    $created_by = User::factory()->create()->id;

    $regionA = Region::factory()->create(['country_id' => $this->country->id]);
    $regionB = Region::factory()->create(['country_id' => $this->country->id]);

    ServiceAccessRecord::create([
        'country_id'                 => $this->country->id,
        'region_id'                  => $regionA->id,
        'year'                       => 2023,
        'electricity_access_percent' => 5.00,
        'source_title'               => 'Test',
        'source_url'                 => 'https://example.com',
        'created_by'                 => $created_by,
    ]);
    ServiceAccessRecord::create([
        'country_id'                 => $this->country->id,
        'region_id'                  => $regionB->id,
        'year'                       => 2023,
        'electricity_access_percent' => 95.00,
        'source_title'               => 'Test',
        'source_url'                 => 'https://example.com',
        'created_by'                 => $created_by,
    ]);

    $this->service->calculateEquityScore($this->country->id, 2023);

    $this->assertDatabaseHas('risk_signals', [
        'country_id'  => $this->country->id,
        'signal_type' => 'regional_equity_deterioration',
        'severity'    => 'high',
    ]);
});

it('does not create a risk signal when equity score improves year-over-year', function () {
    // Establish a low previous equity score (year 2022)
    RegionalEquityMetric::create([
        'country_id'    => $this->country->id,
        'year'          => 2022,
        'equity_score'  => 60.00,
        'calculated_at' => now(),
    ]);

    // No regional records for 2023 → all variances = 0 → equity = 100 (improved)
    // change = (100 - 60) / 60 * 100 ≈ +66.7% → not below threshold → no signal
    $this->service->calculateEquityScore($this->country->id, 2023);

    $this->assertDatabaseMissing('risk_signals', [
        'country_id'  => $this->country->id,
        'signal_type' => 'regional_equity_deterioration',
    ]);
});
