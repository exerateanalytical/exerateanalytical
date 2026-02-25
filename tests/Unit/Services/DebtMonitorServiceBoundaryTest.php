<?php

use App\Models\Country;
use App\Models\NationalDebtRecord;
use App\Services\Fiscal\DebtMonitorService;

beforeEach(function () {
    $this->service = new DebtMonitorService();
    $this->country = Country::factory()->create();
});

it('classifies as low when debt to GDP ratio is exactly 60', function () {
    NationalDebtRecord::factory()->create([
        'country_id'         => $this->country->id,
        'year'               => 2023,
        'debt_to_gdp_ratio'  => 60.00,
        'debt_service_ratio' => 25.00,
    ]);

    $record = $this->service->calculateDebtRisk($this->country->id, 2023);

    expect($record->risk_classification)->toBe('low');
});

it('classifies as moderate when debt to GDP ratio is exactly 70 even with high service ratio', function () {
    NationalDebtRecord::factory()->create([
        'country_id'         => $this->country->id,
        'year'               => 2023,
        'debt_to_gdp_ratio'  => 70.00,
        'debt_service_ratio' => 35.00,
    ]);

    $record = $this->service->calculateDebtRisk($this->country->id, 2023);

    expect($record->risk_classification)->toBe('moderate');
});

it('classifies as elevated when debt to GDP ratio is exactly 90 with service ratio above 30', function () {
    NationalDebtRecord::factory()->create([
        'country_id'         => $this->country->id,
        'year'               => 2023,
        'debt_to_gdp_ratio'  => 90.00,
        'debt_service_ratio' => 35.00,
    ]);

    $record = $this->service->calculateDebtRisk($this->country->id, 2023);

    expect($record->risk_classification)->toBe('elevated');
});

it('classifies as moderate when service ratio is exactly 30 despite ratio above 70', function () {
    NationalDebtRecord::factory()->create([
        'country_id'         => $this->country->id,
        'year'               => 2023,
        'debt_to_gdp_ratio'  => 75.00,
        'debt_service_ratio' => 30.00,
    ]);

    $record = $this->service->calculateDebtRisk($this->country->id, 2023);

    expect($record->risk_classification)->toBe('moderate');
});
