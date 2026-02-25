<?php

use App\Models\BudgetAllocation;
use App\Models\Country;
use App\Models\FiscalRiskSignal;
use App\Services\Fiscal\BudgetTrackerService;

beforeEach(function () {
    $this->service = new BudgetTrackerService();
    $this->country = Country::factory()->create();
});

it('does not flag delay when execution rate is exactly 50', function () {
    $allocation = BudgetAllocation::factory()->create([
        'country_id'       => $this->country->id,
        'year'             => 2023,
        'allocated_amount' => 10000,
        'executed_amount'  => 5000,
    ]);
    $allocation->execution_rate = 50.00;
    $allocation->save();

    $flagged = $this->service->detectDelay($this->country->id, 2023);

    expect($flagged)->toBe(0);

    $allocation->refresh();
    expect($allocation->delay_flag)->toBeFalse();

    expect(FiscalRiskSignal::where('country_id', $this->country->id)
        ->where('year', 2023)
        ->where('risk_type', 'budget_execution')->count())->toBe(0);
});
