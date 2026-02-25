<?php

use App\Exceptions\DataIntegrityException;
use App\Models\Country;
use App\Models\FiscalRiskSignal;
use App\Models\NationalDebtRecord;
use App\Services\Fiscal\DebtMonitorService;

beforeEach(function () {
    $this->service = new DebtMonitorService();
    $this->country = Country::factory()->create();
});

it('returns low classification when debt to GDP ratio is below 60', function () {
    NationalDebtRecord::factory()->create([
        'country_id'        => $this->country->id,
        'year'              => 2023,
        'total_debt'        => 50000,
        'debt_to_gdp_ratio' => 45.00,
        'debt_service_ratio' => 10.00,
    ]);

    $record = $this->service->calculateDebtRisk($this->country->id, 2023);

    expect($record->risk_classification)->toBe('low');
});

it('returns moderate classification when debt to GDP ratio is between 60 and 70', function () {
    NationalDebtRecord::factory()->create([
        'country_id'        => $this->country->id,
        'year'              => 2023,
        'total_debt'        => 65000,
        'debt_to_gdp_ratio' => 65.00,
        'debt_service_ratio' => 20.00,
    ]);

    $record = $this->service->calculateDebtRisk($this->country->id, 2023);

    expect($record->risk_classification)->toBe('moderate');
});

it('returns elevated classification when debt to GDP is above 70 and service ratio exceeds 30', function () {
    NationalDebtRecord::factory()->create([
        'country_id'        => $this->country->id,
        'year'              => 2023,
        'total_debt'        => 75000,
        'debt_to_gdp_ratio' => 75.00,
        'debt_service_ratio' => 35.00,
    ]);

    $record = $this->service->calculateDebtRisk($this->country->id, 2023);

    expect($record->risk_classification)->toBe('elevated');
});

it('returns critical classification when debt to GDP ratio exceeds 90', function () {
    NationalDebtRecord::factory()->create([
        'country_id'        => $this->country->id,
        'year'              => 2023,
        'total_debt'        => 95000,
        'debt_to_gdp_ratio' => 95.00,
        'debt_service_ratio' => 40.00,
    ]);

    $record = $this->service->calculateDebtRisk($this->country->id, 2023);

    expect($record->risk_classification)->toBe('critical');
});

it('creates a FiscalRiskSignal when classification is elevated', function () {
    NationalDebtRecord::factory()->create([
        'country_id'        => $this->country->id,
        'year'              => 2023,
        'total_debt'        => 78000,
        'debt_to_gdp_ratio' => 78.00,
        'debt_service_ratio' => 33.00,
    ]);

    $this->service->calculateDebtRisk($this->country->id, 2023);

    $signal = FiscalRiskSignal::where('country_id', $this->country->id)
        ->where('year', 2023)
        ->where('risk_type', 'debt_sustainability')
        ->first();

    expect($signal)->not->toBeNull();
    expect($signal->severity)->toBe('high');
});

it('creates a FiscalRiskSignal with critical severity when classification is critical', function () {
    NationalDebtRecord::factory()->create([
        'country_id'        => $this->country->id,
        'year'              => 2023,
        'total_debt'        => 92000,
        'debt_to_gdp_ratio' => 92.00,
        'debt_service_ratio' => 45.00,
    ]);

    $this->service->calculateDebtRisk($this->country->id, 2023);

    $signal = FiscalRiskSignal::where('country_id', $this->country->id)
        ->where('year', 2023)
        ->where('severity', 'critical')
        ->first();

    expect($signal)->not->toBeNull();
});

it('does not create FiscalRiskSignal when classification is low', function () {
    NationalDebtRecord::factory()->create([
        'country_id'        => $this->country->id,
        'year'              => 2023,
        'total_debt'        => 40000,
        'debt_to_gdp_ratio' => 40.00,
        'debt_service_ratio' => 12.00,
    ]);

    $this->service->calculateDebtRisk($this->country->id, 2023);

    $signalCount = FiscalRiskSignal::where('country_id', $this->country->id)
        ->where('year', 2023)
        ->count();

    expect($signalCount)->toBe(0);
});

it('throws DataIntegrityException when no debt record is found', function () {
    expect(fn () => $this->service->calculateDebtRisk($this->country->id, 2099))
        ->toThrow(DataIntegrityException::class);
});
