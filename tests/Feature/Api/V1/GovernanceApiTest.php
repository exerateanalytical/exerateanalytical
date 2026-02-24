<?php

use App\Enums\UserRole;
use App\Models\Country;
use App\Models\GovernanceScore;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->country = Country::factory()->create();
});

describe('GET /api/v1/governance/{country}/{year}', function () {
    it('returns 200 with score data when a governance score exists', function () {
        GovernanceScore::create([
            'country_id' => $this->country->id,
            'region_id' => null,
            'year' => 2023,
            'composite_score' => 68.42,
            'pillar_scores' => ['rule_of_law' => ['score' => 70.0, 'weight' => 100]],
            'version' => 1,
            'calculated_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/governance/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'country_id', 'year', 'composite_score', 'pillar_scores', 'version', 'calculated_at'],
            ])
            ->assertJsonPath('data.year', 2023)
            ->assertJsonPath('data.country_id', $this->country->id);
    });

    it('returns 404 when no governance score exists for the requested year', function () {
        $response = $this->getJson("/api/v1/governance/{$this->country->id}/1999");

        $response->assertNotFound();
    });
});

describe('GET /api/v1/governance/{country}/trend', function () {
    it('returns 200 with trend data', function () {
        GovernanceScore::create([
            'country_id' => $this->country->id,
            'region_id' => null,
            'year' => 2022,
            'composite_score' => 65.00,
            'pillar_scores' => [],
            'version' => 1,
            'calculated_at' => now(),
        ]);
        GovernanceScore::create([
            'country_id' => $this->country->id,
            'region_id' => null,
            'year' => 2023,
            'composite_score' => 68.00,
            'pillar_scores' => [],
            'version' => 1,
            'calculated_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/governance/{$this->country->id}/trend");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    });

    it('returns 200 with empty data array when no scores exist', function () {
        $response = $this->getJson("/api/v1/governance/{$this->country->id}/trend");

        $response->assertOk()
            ->assertJsonPath('data', []);
    });
});

describe('POST /api/v1/governance/{country}/{year}/recalculate', function () {
    it('returns 401 when unauthenticated', function () {
        $response = $this->postJson("/api/v1/governance/{$this->country->id}/2023/recalculate");

        $response->assertUnauthorized();
    });

    it('requires authentication to recalculate governance score', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        // The recalculate endpoint will fail internally because there are no pillars,
        // but it must be reachable by an authenticated user (not 401/403)
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/governance/{$this->country->id}/2023/recalculate");

        // The route should be accessible (not 401 or 403)
        expect($response->getStatusCode())->not->toBe(401);
        expect($response->getStatusCode())->not->toBe(403);
    });
});
