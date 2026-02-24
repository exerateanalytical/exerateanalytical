<?php

use App\Models\Country;
use App\Models\ServiceAccessRecord;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->country = Country::factory()->create();
});

describe('GET /api/v1/development/{country}/{year}', function () {
    it('returns 200 with service access data when a record exists', function () {
        ServiceAccessRecord::create([
            'country_id' => $this->country->id,
            'region_id' => null,
            'year' => 2023,
            'electricity_access_percent' => 72.50,
            'safe_water_access_percent' => 65.00,
            'road_density_km_per_100km2' => 12.30,
            'internet_penetration_percent' => 45.00,
        ]);

        $response = $this->getJson("/api/v1/development/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'country_id', 'year',
                    'electricity_access_percent', 'safe_water_access_percent',
                    'internet_penetration_percent',
                ],
            ])
            ->assertJsonPath('data.year', 2023)
            ->assertJsonPath('data.country_id', $this->country->id);
    });

    it('returns 404 when no service access record exists for the requested year', function () {
        $response = $this->getJson("/api/v1/development/{$this->country->id}/1980");

        $response->assertNotFound()
            ->assertJsonPath('message', 'No service access record found.');
    });
});

describe('GET /api/v1/development/{country}/trend', function () {
    it('returns 200 with trend data when records exist', function () {
        foreach ([2021, 2022, 2023] as $year) {
            ServiceAccessRecord::create([
                'country_id' => $this->country->id,
                'region_id' => null,
                'year' => $year,
                'electricity_access_percent' => 60.00 + ($year - 2021) * 5,
            ]);
        }

        $response = $this->getJson("/api/v1/development/{$this->country->id}/trend");

        $response->assertOk()
            ->assertJsonStructure(['data']);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThanOrEqual(1);
    });

    it('returns 200 with empty data when no trend records exist', function () {
        $response = $this->getJson("/api/v1/development/{$this->country->id}/trend");

        $response->assertOk()
            ->assertJsonPath('data', []);
    });
});
