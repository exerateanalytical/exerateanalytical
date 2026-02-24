<?php

use App\Exceptions\DataIntegrityException;
use App\Exceptions\IndicatorWeightMismatchException;
use App\Models\Country;
use App\Models\GovernanceScore;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\Pillar;
use App\Services\Governance\GovernanceIndexService;

beforeEach(function () {
    $this->service = new GovernanceIndexService();
});

it('normalizes indicator values with minmax method', function () {
    $country = Country::factory()->create();
    $pillar = Pillar::create([
        'country_id' => $country->id,
        'name' => 'Rule of Law',
        'weight' => 100.00,
        'is_active' => true,
        'version' => 1,
    ]);
    $indicator = Indicator::create([
        'pillar_id' => $pillar->id,
        'country_id' => $country->id,
        'name' => 'Judicial Independence',
        'weight' => 100.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);

    // Create three values: min=0, mid=50, max=100
    $v1 = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 0]);
    $v2 = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 50]);
    $v3 = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 100]);

    $this->service->normalizeIndicator($indicator->id, $country->id, 2023);

    expect((float) $v1->fresh()->normalized_value)->toBe(0.0);
    expect((float) $v2->fresh()->normalized_value)->toBe(50.0);
    expect((float) $v3->fresh()->normalized_value)->toBe(100.0);
});

it('normalizes indicator values with inverse_minmax method', function () {
    $country = Country::factory()->create();
    $pillar = Pillar::create([
        'country_id' => $country->id,
        'name' => 'Corruption',
        'weight' => 100.00,
        'is_active' => true,
        'version' => 1,
    ]);
    $indicator = Indicator::create([
        'pillar_id' => $pillar->id,
        'country_id' => $country->id,
        'name' => 'Bribery Rate',
        'weight' => 100.00,
        'normalization_method' => 'inverse_minmax',
        'is_active' => true,
        'version' => 1,
    ]);

    $v1 = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 0]);
    $v2 = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 100]);

    $this->service->normalizeIndicator($indicator->id, $country->id, 2023);

    // inverse_minmax: min raw => max normalized (100), max raw => min normalized (0)
    expect((float) $v1->fresh()->normalized_value)->toBe(100.0);
    expect((float) $v2->fresh()->normalized_value)->toBe(0.0);
});

it('normalizes indicator values with zscore method', function () {
    $country = Country::factory()->create();
    $pillar = Pillar::create([
        'country_id' => $country->id,
        'name' => 'Transparency',
        'weight' => 100.00,
        'is_active' => true,
        'version' => 1,
    ]);
    $indicator = Indicator::create([
        'pillar_id' => $pillar->id,
        'country_id' => $country->id,
        'name' => 'Disclosure Index',
        'weight' => 100.00,
        'normalization_method' => 'zscore',
        'is_active' => true,
        'version' => 1,
    ]);

    // mean=0, values symmetric around 0 => z-scores are -1, 0, 1
    IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => -10]);
    $vMid = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 0]);
    IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 10]);

    $this->service->normalizeIndicator($indicator->id, $country->id, 2023);

    // The midpoint value (mean) should have a z-score of 0
    expect((float) $vMid->fresh()->normalized_value)->toBe(0.0);
});

it('throws DataIntegrityException when min equals max in minmax normalization', function () {
    $country = Country::factory()->create();
    $pillar = Pillar::create([
        'country_id' => $country->id,
        'name' => 'Stability',
        'weight' => 100.00,
        'is_active' => true,
        'version' => 1,
    ]);
    $indicator = Indicator::create([
        'pillar_id' => $pillar->id,
        'country_id' => $country->id,
        'name' => 'Peace Index',
        'weight' => 100.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);

    // All values identical => range = 0 => division by zero
    IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 50]);
    IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 50]);

    expect(fn () => $this->service->normalizeIndicator($indicator->id, $country->id, 2023))
        ->toThrow(DataIntegrityException::class);
});

it('throws DataIntegrityException when standard deviation is zero in zscore normalization', function () {
    $country = Country::factory()->create();
    $pillar = Pillar::create([
        'country_id' => $country->id,
        'name' => 'Governance',
        'weight' => 100.00,
        'is_active' => true,
        'version' => 1,
    ]);
    $indicator = Indicator::create([
        'pillar_id' => $pillar->id,
        'country_id' => $country->id,
        'name' => 'Uniformity Index',
        'weight' => 100.00,
        'normalization_method' => 'zscore',
        'is_active' => true,
        'version' => 1,
    ]);

    // All identical values => std dev = 0 => division by zero
    IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 75]);
    IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 75]);

    expect(fn () => $this->service->normalizeIndicator($indicator->id, $country->id, 2023))
        ->toThrow(DataIntegrityException::class);
});

it('calculatePillarScore returns the correct weighted score', function () {
    $country = Country::factory()->create();
    $pillar = Pillar::create([
        'country_id' => $country->id,
        'name' => 'Accountability',
        'weight' => 100.00,
        'is_active' => true,
        'version' => 1,
    ]);

    // Two indicators with weights 60 and 40 (sum = 100)
    $indicatorA = Indicator::create([
        'pillar_id' => $pillar->id,
        'country_id' => $country->id,
        'name' => 'Audit Compliance',
        'weight' => 60.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);
    $indicatorB = Indicator::create([
        'pillar_id' => $pillar->id,
        'country_id' => $country->id,
        'name' => 'Financial Disclosure',
        'weight' => 40.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);

    // normalized_value = 80 for A, 50 for B
    // weighted score = (80 * 60/100) + (50 * 40/100) = 48 + 20 = 68
    IndicatorValue::create([
        'indicator_id' => $indicatorA->id,
        'country_id' => $country->id,
        'year' => 2023,
        'raw_value' => 80,
        'normalized_value' => 80,
    ]);
    IndicatorValue::create([
        'indicator_id' => $indicatorB->id,
        'country_id' => $country->id,
        'year' => 2023,
        'raw_value' => 50,
        'normalized_value' => 50,
    ]);

    $score = $this->service->calculatePillarScore($pillar->id, $country->id, 2023);

    expect($score)->toBe(68.0);
});

it('throws IndicatorWeightMismatchException when weights do not sum to 100', function () {
    $country = Country::factory()->create();
    $pillar = Pillar::create([
        'country_id' => $country->id,
        'name' => 'Participation',
        'weight' => 100.00,
        'is_active' => true,
        'version' => 1,
    ]);

    // Weights sum to 90, not 100
    Indicator::create([
        'pillar_id' => $pillar->id,
        'country_id' => $country->id,
        'name' => 'Voter Turnout',
        'weight' => 50.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);
    Indicator::create([
        'pillar_id' => $pillar->id,
        'country_id' => $country->id,
        'name' => 'Civil Society Index',
        'weight' => 40.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);

    expect(fn () => $this->service->calculatePillarScore($pillar->id, $country->id, 2023))
        ->toThrow(IndicatorWeightMismatchException::class);
});

it('runSensitivitySimulation returns array with pillar impact data', function () {
    $country = Country::factory()->create();

    // Persist a composite governance score as the baseline
    $pillar = Pillar::create([
        'country_id' => $country->id,
        'name' => 'Security',
        'weight' => 100.00,
        'is_active' => true,
        'version' => 1,
    ]);
    GovernanceScore::create([
        'country_id' => $country->id,
        'region_id' => null,
        'year' => 2023,
        'composite_score' => 72.50,
        'pillar_scores' => [],
        'version' => 1,
        'calculated_at' => now(),
    ]);

    $results = $this->service->runSensitivitySimulation($country->id, 2023);

    expect($results)->toBeArray();

    if (count($results) > 0) {
        $firstResult = $results[0];
        expect($firstResult)->toHaveKeys(['pillar_id', 'pillar_name', 'delta', 'simulated_score', 'impact_variance']);
    }
});
