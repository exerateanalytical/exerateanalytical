<?php

use App\Exceptions\DataIntegrityException;
use App\Models\Country;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use App\Models\Pillar;
use App\Services\Governance\GovernanceIndexService;

beforeEach(function () {
    $this->service = new GovernanceIndexService();
    $this->country = Country::factory()->create();
    $this->pillar = Pillar::create([
        'country_id' => $this->country->id,
        'name' => 'Test Pillar',
        'weight' => 100.00,
        'is_active' => true,
        'version' => 1,
    ]);
});

it('minmax normalization with all identical values throws DataIntegrityException', function () {
    $indicator = Indicator::create([
        'pillar_id' => $this->pillar->id,
        'country_id' => $this->country->id,
        'name' => 'Flat Indicator',
        'weight' => 100.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);

    foreach ([42, 42, 42] as $val) {
        IndicatorValue::create([
            'indicator_id' => $indicator->id,
            'country_id' => $this->country->id,
            'year' => 2023,
            'raw_value' => $val,
        ]);
    }

    expect(fn () => $this->service->normalizeIndicator($indicator->id, $this->country->id, 2023))
        ->toThrow(DataIntegrityException::class, 'Division by zero: min equals max');
});

it('zscore normalization with fewer than 2 values throws DataIntegrityException', function () {
    $indicator = Indicator::create([
        'pillar_id' => $this->pillar->id,
        'country_id' => $this->country->id,
        'name' => 'Singleton Indicator',
        'weight' => 100.00,
        'normalization_method' => 'zscore',
        'is_active' => true,
        'version' => 1,
    ]);

    IndicatorValue::create([
        'indicator_id' => $indicator->id,
        'country_id' => $this->country->id,
        'year' => 2023,
        'raw_value' => 55,
    ]);

    expect(fn () => $this->service->normalizeIndicator($indicator->id, $this->country->id, 2023))
        ->toThrow(DataIntegrityException::class, 'at least 2 values required');
});

it('minmax normalization with negative values produces correct results', function () {
    $indicator = Indicator::create([
        'pillar_id' => $this->pillar->id,
        'country_id' => $this->country->id,
        'name' => 'Negative Range Indicator',
        'weight' => 100.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);

    // range from -100 to 100, span = 200
    $vMin = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $this->country->id, 'year' => 2023, 'raw_value' => -100]);
    $vMid = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $this->country->id, 'year' => 2023, 'raw_value' => 0]);
    $vMax = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $this->country->id, 'year' => 2023, 'raw_value' => 100]);

    $this->service->normalizeIndicator($indicator->id, $this->country->id, 2023);

    // min maps to 0, mid maps to 50, max maps to 100
    expect((float) $vMin->fresh()->normalized_value)->toBe(0.0);
    expect((float) $vMid->fresh()->normalized_value)->toBe(50.0);
    expect((float) $vMax->fresh()->normalized_value)->toBe(100.0);
});

it('minmax normalization with extreme outliers maps correctly without clamping', function () {
    $indicator = Indicator::create([
        'pillar_id' => $this->pillar->id,
        'country_id' => $this->country->id,
        'name' => 'Outlier Indicator',
        'weight' => 100.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);

    // Values: 0, 50, 1000 (extreme outlier)
    $vMin  = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $this->country->id, 'year' => 2023, 'raw_value' => 0]);
    $vMid  = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $this->country->id, 'year' => 2023, 'raw_value' => 50]);
    $vMax  = IndicatorValue::create(['indicator_id' => $indicator->id, 'country_id' => $this->country->id, 'year' => 2023, 'raw_value' => 1000]);

    $this->service->normalizeIndicator($indicator->id, $this->country->id, 2023);

    // min = 0 maps to 0, max = 1000 maps to 100, mid = 50 maps to 5.0
    expect((float) $vMin->fresh()->normalized_value)->toBe(0.0);
    expect((float) $vMax->fresh()->normalized_value)->toBe(100.0);
    expect((float) $vMid->fresh()->normalized_value)->toBe(5.0);
});

it('inverse_minmax produces the opposite ranking to minmax', function () {
    $country = $this->country;

    $indicatorMinMax = Indicator::create([
        'pillar_id' => $this->pillar->id,
        'country_id' => $country->id,
        'name' => 'Forward Indicator',
        'weight' => 50.00,
        'normalization_method' => 'minmax',
        'is_active' => true,
        'version' => 1,
    ]);
    $indicatorInverse = Indicator::create([
        'pillar_id' => $this->pillar->id,
        'country_id' => $country->id,
        'name' => 'Inverse Indicator',
        'weight' => 50.00,
        'normalization_method' => 'inverse_minmax',
        'is_active' => true,
        'version' => 1,
    ]);

    // Same raw values for both indicators
    foreach ([$indicatorMinMax, $indicatorInverse] as $ind) {
        IndicatorValue::create(['indicator_id' => $ind->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 20]);
        IndicatorValue::create(['indicator_id' => $ind->id, 'country_id' => $country->id, 'year' => 2023, 'raw_value' => 80]);
    }

    $this->service->normalizeIndicator($indicatorMinMax->id, $country->id, 2023);
    $this->service->normalizeIndicator($indicatorInverse->id, $country->id, 2023);

    $mmValues  = IndicatorValue::where('indicator_id', $indicatorMinMax->id)->orderBy('raw_value')->pluck('normalized_value')->map(fn ($v) => (float) $v)->toArray();
    $invValues = IndicatorValue::where('indicator_id', $indicatorInverse->id)->orderBy('raw_value')->pluck('normalized_value')->map(fn ($v) => (float) $v)->toArray();

    // minmax: [0.0, 100.0], inverse_minmax: [100.0, 0.0] — opposite ordering
    expect($mmValues[0])->toBe(0.0);
    expect($mmValues[1])->toBe(100.0);
    expect($invValues[0])->toBe(100.0);
    expect($invValues[1])->toBe(0.0);
});
