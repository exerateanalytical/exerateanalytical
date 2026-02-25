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

// ─────────────────────────────────────────────────────────────────────────────
// Batch 4 — Phase 2A: Service Layer Test Hardening
// ─────────────────────────────────────────────────────────────────────────────

// 1. Composite score happy path ───────────────────────────────────────────────

it('calculateCompositeScore returns the correct weighted composite score and persists the record', function () {
    $country = Country::factory()->create();

    // Two pillars: 60% and 40% weight (sum = 100 — passes pillar weight guard)
    $pillarA = Pillar::factory()->create([
        'country_id' => $country->id,
        'name'       => 'Rule of Law',
        'weight'     => 60.00,
    ]);
    $pillarB = Pillar::factory()->create([
        'country_id' => $country->id,
        'name'       => 'Accountability',
        'weight'     => 40.00,
    ]);

    // Each pillar has one indicator at weight 100 (passes indicator weight guard)
    $indA = Indicator::factory()->create([
        'pillar_id'  => $pillarA->id,
        'country_id' => $country->id,
        'weight'     => 100.00,
    ]);
    $indB = Indicator::factory()->create([
        'pillar_id'  => $pillarB->id,
        'country_id' => $country->id,
        'weight'     => 100.00,
    ]);

    // Pillar A score = 80.0 * (100/100) = 80.0
    // Pillar B score = 50.0 * (100/100) = 50.0
    // composite     = (80.0 * 60/100) + (50.0 * 40/100) = 48.0 + 20.0 = 68.0
    IndicatorValue::factory()->create([
        'indicator_id'     => $indA->id,
        'country_id'       => $country->id,
        'year'             => 2024,
        'raw_value'        => 80,
        'normalized_value' => 80.0,
    ]);
    IndicatorValue::factory()->create([
        'indicator_id'     => $indB->id,
        'country_id'       => $country->id,
        'year'             => 2024,
        'raw_value'        => 50,
        'normalized_value' => 50.0,
    ]);

    $record = $this->service->calculateCompositeScore($country->id, null, 2024);

    expect($record)->toBeInstanceOf(GovernanceScore::class);
    expect((float) $record->composite_score)->toBe(68.0);
    expect($record->country_id)->toBe($country->id);
    expect($record->region_id)->toBeNull();
    expect($record->year)->toBe(2024);

    // Confirm the record was persisted to the database
    $persisted = GovernanceScore::where('country_id', $country->id)
        ->where('year', 2024)
        ->whereNull('region_id')
        ->first();

    expect($persisted)->not->toBeNull();
    expect((float) $persisted->composite_score)->toBe(68.0);
});

// 2. Pillar weight mismatch at composite level ─────────────────────────────────

it('calculateCompositeScore throws IndicatorWeightMismatchException when pillar weights do not sum to 100', function () {
    $country = Country::factory()->create();

    // Pillar weights: 60 + 30 = 90 ≠ 100
    Pillar::factory()->create(['country_id' => $country->id, 'weight' => 60.00]);
    Pillar::factory()->create(['country_id' => $country->id, 'weight' => 30.00]);

    expect(fn () => $this->service->calculateCompositeScore($country->id, null, 2024))
        ->toThrow(IndicatorWeightMismatchException::class);
});

// 3. Missing normalized value throws at pillar score ───────────────────────────

it('calculatePillarScore throws DataIntegrityException when an indicator has no normalized value', function () {
    $country   = Country::factory()->create();
    $pillar    = Pillar::factory()->create(['country_id' => $country->id, 'weight' => 100.00]);
    $indicator = Indicator::factory()->create([
        'pillar_id'  => $pillar->id,
        'country_id' => $country->id,
        'weight'     => 100.00,
    ]);

    // Factory defaults normalized_value to null — no normalization has been run
    IndicatorValue::factory()->create([
        'indicator_id'     => $indicator->id,
        'country_id'       => $country->id,
        'year'             => 2024,
        'normalized_value' => null,
    ]);

    expect(fn () => $this->service->calculatePillarScore($pillar->id, $country->id, 2024))
        ->toThrow(DataIntegrityException::class);
});

// 4. Z-score with fewer than 2 values throws ──────────────────────────────────

it('throws DataIntegrityException when fewer than 2 values are provided for zscore normalization', function () {
    $country   = Country::factory()->create();
    $pillar    = Pillar::factory()->create(['country_id' => $country->id]);
    $indicator = Indicator::factory()->create([
        'pillar_id'            => $pillar->id,
        'country_id'           => $country->id,
        'normalization_method' => 'zscore',
    ]);

    // Single value — insufficient for z-score (requires at least 2)
    IndicatorValue::factory()->create([
        'indicator_id' => $indicator->id,
        'country_id'   => $country->id,
        'year'         => 2024,
    ]);

    expect(fn () => $this->service->normalizeIndicator($indicator->id, $country->id, 2024))
        ->toThrow(DataIntegrityException::class, 'at least 2 values required');
});

// 5. getCompositeScore returns null when no record exists ──────────────────────

it('getCompositeScore returns null when no governance score record exists for the country and year', function () {
    $country = Country::factory()->create();

    $result = $this->service->getCompositeScore($country->id, 2024);

    expect($result)->toBeNull();
});

// 6. runSensitivitySimulation baseline guard ───────────────────────────────────

it('runSensitivitySimulation throws DataIntegrityException when no baseline composite score exists', function () {
    $country = Country::factory()->create();
    Pillar::factory()->create(['country_id' => $country->id]);

    // No GovernanceScore record exists — baseline is null
    expect(fn () => $this->service->runSensitivitySimulation($country->id, 2024))
        ->toThrow(DataIntegrityException::class);
});

// 7. Trend ordering and limit ─────────────────────────────────────────────────

it('getTrend returns national scores ordered by year descending and respects the limit', function () {
    $country = Country::factory()->create();

    // Create 6 national scores for consecutive years 2019–2024
    foreach (range(2019, 2024) as $year) {
        GovernanceScore::factory()->create([
            'country_id' => $country->id,
            'region_id'  => null,
            'year'       => $year,
        ]);
    }

    $trend = $this->service->getTrend($country->id, 5);

    expect($trend)->toHaveCount(5);

    // Most recent year must be first
    expect($trend->first()->year)->toBe(2024);
    expect($trend->last()->year)->toBe(2020);

    // Full descending year sequence
    expect($trend->pluck('year')->toArray())->toBe([2024, 2023, 2022, 2021, 2020]);
});
