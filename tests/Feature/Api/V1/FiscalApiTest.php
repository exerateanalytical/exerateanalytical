<?php

use App\Models\BudgetAllocation;
use App\Models\Country;
use App\Models\FiscalRiskSignal;
use App\Models\NationalDebtRecord;
use App\Models\RevenueRecord;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->country = Country::factory()->create();
});

describe('GET /api/v1/fiscal/debt/{country}/{year}', function () {
    it('returns 200 with debt record data when a record exists', function () {
        NationalDebtRecord::create([
            'country_id' => $this->country->id,
            'year' => 2023,
            'total_debt' => 55000000000,
            'debt_to_gdp_ratio' => 55.50,
            'external_debt' => 30000000000,
            'domestic_debt' => 25000000000,
            'debt_service_ratio' => 18.00,
            'risk_classification' => 'low',
        ]);

        $response = $this->getJson("/api/v1/fiscal/debt/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'country_id', 'year', 'total_debt', 'debt_to_gdp_ratio', 'risk_classification'],
            ])
            ->assertJsonPath('data.year', 2023)
            ->assertJsonPath('data.country_id', $this->country->id);
    });

    it('returns 404 when no debt record exists for the requested year', function () {
        $response = $this->getJson("/api/v1/fiscal/debt/{$this->country->id}/1990");

        $response->assertNotFound()
            ->assertJsonPath('message', 'No debt record found.');
    });
});

describe('GET /api/v1/fiscal/budget/{country}/{year}', function () {
    it('returns 200 with budget allocations', function () {
        BudgetAllocation::create([
            'country_id' => $this->country->id,
            'year' => 2023,
            'sector_name' => 'Education',
            'allocated_amount' => 10000000,
            'executed_amount' => 9000000,
            'execution_rate' => 90.00,
            'delay_flag' => false,
        ]);

        $response = $this->getJson("/api/v1/fiscal/budget/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    });

    it('returns 200 with empty data when no budget allocations exist', function () {
        $response = $this->getJson("/api/v1/fiscal/budget/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonPath('data', []);
    });
});

describe('GET /api/v1/fiscal/revenue/{country}/{year}', function () {
    it('returns 200 with revenue record when one exists', function () {
        RevenueRecord::create([
            'country_id' => $this->country->id,
            'year' => 2023,
            'total_revenue' => 8000000000,
            'tax_revenue' => 6000000000,
            'non_tax_revenue' => 1500000000,
            'grants' => 500000000,
            'revenue_to_gdp_ratio' => 18.50,
        ]);

        $response = $this->getJson("/api/v1/fiscal/revenue/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'country_id', 'year', 'total_revenue', 'tax_revenue'],
            ]);
    });

    it('returns 404 when no revenue record exists', function () {
        $response = $this->getJson("/api/v1/fiscal/revenue/{$this->country->id}/1985");

        $response->assertNotFound();
    });
});

describe('GET /api/v1/fiscal/risk/{country}/{year}', function () {
    it('returns 200 with risk signals', function () {
        FiscalRiskSignal::create([
            'country_id' => $this->country->id,
            'year' => 2023,
            'risk_type' => 'debt_sustainability',
            'severity' => 'high',
            'description' => 'Debt ratio elevated at 78%.',
            'triggered_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/fiscal/risk/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'country_id', 'year', 'risk_type', 'severity']]]);
    });

    it('returns 200 with empty data array when no risk signals exist', function () {
        $response = $this->getJson("/api/v1/fiscal/risk/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonPath('data', []);
    });
});
