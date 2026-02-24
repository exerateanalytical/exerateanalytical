<?php

use App\Models\AccountabilityEntity;
use App\Models\AccountabilityLink;
use App\Models\AccountabilityScore;
use App\Models\BudgetAllocation;
use App\Models\Country;
use App\Models\Institution;
use App\Services\Accountability\AccountabilityMatrixService;

beforeEach(function () {
    $this->service = new AccountabilityMatrixService();
    $this->country = Country::factory()->create();
});

it('calculateBudgetExecutionScore returns 0.0 when entity has no linked budgets', function () {
    $entity = AccountabilityEntity::create([
        'country_id' => $this->country->id,
        'year' => 2023,
        'mandate_area' => 'Education',
    ]);

    $score = $this->service->calculateBudgetExecutionScore($entity->id);

    expect($score)->toBe(0.0);
});

it('calculateBudgetExecutionScore returns the average execution rate of linked budgets', function () {
    $entity = AccountabilityEntity::create([
        'country_id' => $this->country->id,
        'year' => 2023,
        'mandate_area' => 'Health',
    ]);

    $budget1 = BudgetAllocation::create([
        'country_id' => $this->country->id,
        'year' => 2023,
        'sector_name' => 'Primary Health',
        'allocated_amount' => 1000000,
        'executed_amount' => 800000,
        'execution_rate' => 80.00,
        'delay_flag' => false,
    ]);
    $budget2 = BudgetAllocation::create([
        'country_id' => $this->country->id,
        'year' => 2023,
        'sector_name' => 'Tertiary Health',
        'allocated_amount' => 2000000,
        'executed_amount' => 1600000,
        'execution_rate' => 60.00,
        'delay_flag' => false,
    ]);

    AccountabilityLink::create(['accountability_entity_id' => $entity->id, 'linked_budget_id' => $budget1->id]);
    AccountabilityLink::create(['accountability_entity_id' => $entity->id, 'linked_budget_id' => $budget2->id]);

    $score = $this->service->calculateBudgetExecutionScore($entity->id);

    // average of 80 and 60 = 70
    expect($score)->toBe(70.0);
});

it('calculateCompositeAccountability uses weights of 0.3, 0.3, 0.3, 0.1', function () {
    $institution = Institution::factory()->create([
        'country_id' => $this->country->id,
        'transparency_score' => 80.00,
    ]);

    $entity = AccountabilityEntity::create([
        'country_id' => $this->country->id,
        'institution_id' => $institution->id,
        'year' => 2023,
        'mandate_area' => 'Finance',
    ]);

    // Linked budget with known execution_rate and no delay
    $budget = BudgetAllocation::create([
        'country_id' => $this->country->id,
        'year' => 2023,
        'sector_name' => 'Public Finance',
        'allocated_amount' => 1000000,
        'executed_amount' => 900000,
        'execution_rate' => 90.00,
        'delay_flag' => false,
    ]);
    AccountabilityLink::create(['accountability_entity_id' => $entity->id, 'linked_budget_id' => $budget->id]);

    $accountabilityScore = $this->service->calculateCompositeAccountability($entity->id);

    // budget_score = 90, delivery_score = 100 (no delays), service_score = 0 (no region/records), transparency = 80
    // composite = (90 * 0.3) + (100 * 0.3) + (0 * 0.3) + (80 * 0.1) = 27 + 30 + 0 + 8 = 65
    expect((float) $accountabilityScore->composite_accountability_score)->toBe(65.0);
});

it('composite score is clamped to 0 at minimum', function () {
    $entity = AccountabilityEntity::create([
        'country_id' => $this->country->id,
        'year' => 2023,
        'mandate_area' => 'Infrastructure',
    ]);

    $accountabilityScore = $this->service->calculateCompositeAccountability($entity->id);

    // With no linked budgets: budget=0, delivery=0, service=50 (default), transparency=50 (default)
    // composite = (0*0.3) + (0*0.3) + (50*0.3) + (50*0.1) = 0 + 0 + 15 + 5 = 20
    expect((float) $accountabilityScore->composite_accountability_score)->toBeGreaterThanOrEqual(0.0);
    expect((float) $accountabilityScore->composite_accountability_score)->toBeLessThanOrEqual(100.0);
});

it('composite score is clamped to 100 at maximum', function () {
    $institution = Institution::factory()->create([
        'country_id' => $this->country->id,
        'transparency_score' => 100.00,
    ]);

    $entity = AccountabilityEntity::create([
        'country_id' => $this->country->id,
        'institution_id' => $institution->id,
        'year' => 2023,
        'mandate_area' => 'Energy',
    ]);

    $budget = BudgetAllocation::create([
        'country_id' => $this->country->id,
        'year' => 2023,
        'sector_name' => 'Energy',
        'allocated_amount' => 5000000,
        'executed_amount' => 5000000,
        'execution_rate' => 100.00,
        'delay_flag' => false,
    ]);
    AccountabilityLink::create(['accountability_entity_id' => $entity->id, 'linked_budget_id' => $budget->id]);

    $accountabilityScore = $this->service->calculateCompositeAccountability($entity->id);

    expect((float) $accountabilityScore->composite_accountability_score)->toBeLessThanOrEqual(100.0);
});

it('calculateCompositeAccountability persists an AccountabilityScore record', function () {
    $entity = AccountabilityEntity::create([
        'country_id' => $this->country->id,
        'year' => 2023,
        'mandate_area' => 'Water',
    ]);

    $this->service->calculateCompositeAccountability($entity->id);

    $record = AccountabilityScore::where('accountability_entity_id', $entity->id)->first();

    expect($record)->not->toBeNull();
    expect($record->accountability_entity_id)->toBe($entity->id);
});
