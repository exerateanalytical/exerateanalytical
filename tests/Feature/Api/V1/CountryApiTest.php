<?php

use App\Enums\RiskTier;
use App\Enums\UserRole;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

describe('GET /api/v1/countries', function () {
    it('returns a paginated list of countries as a public endpoint', function () {
        Country::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/countries');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'iso_code', 'continent_region', 'risk_tier', 'is_active']],
                'meta' => ['current_page', 'total'],
            ]);
    });
});

describe('POST /api/v1/countries', function () {
    it('allows SuperAdmin to create a country', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/countries', [
                'name' => 'Test Country',
                'iso_code' => 'TCC',
                'continent_region' => 'West Africa',
                'risk_tier' => RiskTier::Low->value,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.iso_code', 'TCC');
    });

    it('denies DataAnalyst from creating a country', function () {
        $analyst = User::factory()->create();
        $analyst->assignRole(UserRole::DataAnalyst->value);

        $response = $this->actingAs($analyst, 'sanctum')
            ->postJson('/api/v1/countries', [
                'name' => 'Test Country',
                'iso_code' => 'TCC',
                'continent_region' => 'West Africa',
                'risk_tier' => RiskTier::Low->value,
            ]);

        $response->assertForbidden();
    });

    it('rejects invalid iso_code', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/countries', [
                'name' => 'Test Country',
                'iso_code' => 'TOOLONG',
                'continent_region' => 'West Africa',
                'risk_tier' => RiskTier::Low->value,
            ]);

        $response->assertUnprocessable();
    });
});

describe('DELETE /api/v1/countries/{id}', function () {
    it('allows SuperAdmin to soft-delete a country', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        $country = Country::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/countries/{$country->id}");

        $response->assertOk();
        $this->assertSoftDeleted('countries', ['id' => $country->id]);
    });
});
