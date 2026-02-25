<?php

use App\Enums\UserRole;
use App\Exceptions\DataIntegrityException;
use App\Models\Country;
use App\Models\GovernanceScore;
use App\Models\Region;
use App\Models\User;
use App\Services\Governance\GovernanceIndexService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->country = Country::factory()->create();
});

// ─────────────────────────────────────────────────────────────────────────────
// show()
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/v1/governance/{country}/{year}', function () {
    it('returns 200 with the full resource including null region_id for a national score', function () {
        GovernanceScore::factory()->create([
            'country_id'      => $this->country->id,
            'year'            => 2023,
            'composite_score' => 68.42,
            'pillar_scores'   => ['rule_of_law' => ['score' => 70.0, 'weight' => 100]],
        ]);

        $response = $this->getJson("/api/v1/governance/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'country_id', 'region_id', 'year', 'composite_score', 'pillar_scores', 'version', 'calculated_at'],
            ])
            ->assertJsonPath('data.country_id', $this->country->id)
            ->assertJsonPath('data.year', 2023)
            ->assertJsonPath('data.region_id', null);
    });

    it('returns 404 when no national score exists for the requested year', function () {
        $response = $this->getJson("/api/v1/governance/{$this->country->id}/1999");

        $response->assertNotFound()
            ->assertJsonPath('status', 'error');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// showRegional()
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/v1/governance/{country}/{region}/{year}', function () {
    it('returns 200 with the regional governance score when one exists', function () {
        $region = Region::factory()->create(['country_id' => $this->country->id]);

        GovernanceScore::factory()->create([
            'country_id'      => $this->country->id,
            'region_id'       => $region->id,
            'year'            => 2024,
            'composite_score' => 55.30,
        ]);

        $response = $this->getJson("/api/v1/governance/{$this->country->id}/{$region->id}/2024");

        $response->assertOk()
            ->assertJsonPath('data.region_id', $region->id)
            ->assertJsonPath('data.year', 2024);
    });

    it('returns 404 when no regional score exists', function () {
        $region = Region::factory()->create(['country_id' => $this->country->id]);

        $response = $this->getJson("/api/v1/governance/{$this->country->id}/{$region->id}/2024");

        $response->assertNotFound();
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// trend()
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/v1/governance/{country}/trend', function () {
    it('returns 200 with trend data', function () {
        GovernanceScore::factory()->create([
            'country_id'      => $this->country->id,
            'year'            => 2022,
            'composite_score' => 65.00,
        ]);
        GovernanceScore::factory()->create([
            'country_id'      => $this->country->id,
            'year'            => 2023,
            'composite_score' => 68.00,
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

    it('returns years in descending order and caps results at 5 records', function () {
        foreach (range(2018, 2024) as $year) {
            GovernanceScore::factory()->create([
                'country_id'      => $this->country->id,
                'year'            => $year,
                'composite_score' => 50.00 + ($year - 2018),
            ]);
        }

        $response = $this->getJson("/api/v1/governance/{$this->country->id}/trend");

        $response->assertOk();

        $data = $response->json('data');

        expect($data)->toHaveCount(5);
        expect(array_column($data, 'year'))->toBe([2024, 2023, 2022, 2021, 2020]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// recalculate()
// ─────────────────────────────────────────────────────────────────────────────

describe('POST /api/v1/governance/{country}/{year}/recalculate', function () {
    it('returns 401 when unauthenticated', function () {
        $response = $this->postJson("/api/v1/governance/{$this->country->id}/2023/recalculate");

        $response->assertUnauthorized();
    });

    it('returns a success response with the recalculated score when authorized', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        $scoreRecord = GovernanceScore::factory()->create([
            'country_id'      => $this->country->id,
            'year'            => 2024,
            'composite_score' => 72.50,
        ]);

        $this->mock(GovernanceIndexService::class, function ($mock) use ($scoreRecord) {
            $mock->shouldReceive('calculateCompositeScore')
                ->once()
                ->andReturn($scoreRecord);
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/governance/{$this->country->id}/2024/recalculate");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Governance score recalculated.')
            ->assertJsonStructure(['data' => ['id', 'country_id', 'year', 'composite_score']]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// sensitivity()
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/v1/governance/{country}/{year}/sensitivity', function () {
    it('returns a simulation array when a baseline score exists', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        $this->mock(GovernanceIndexService::class, function ($mock) {
            $mock->shouldReceive('runSensitivitySimulation')
                ->once()
                ->andReturn([
                    [
                        'pillar_id'       => 'p1',
                        'pillar_name'     => 'Rule of Law',
                        'delta'           => 5,
                        'simulated_score' => 72.5000,
                        'impact_variance' => 2.5000,
                    ],
                ]);
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/governance/{$this->country->id}/2024/sensitivity");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['pillar_id', 'pillar_name', 'delta', 'simulated_score', 'impact_variance']],
            ]);
    });

    it('returns 422 when no baseline composite score exists', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::SuperAdmin->value);

        $this->mock(GovernanceIndexService::class, function ($mock) {
            $mock->shouldReceive('runSensitivitySimulation')
                ->andThrow(new DataIntegrityException(
                    'No baseline composite score found for country [x] in year [2024]. Run calculateCompositeScore() first.'
                ));
        });

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/governance/{$this->country->id}/2024/sensitivity");

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Route constraints
// ─────────────────────────────────────────────────────────────────────────────

describe('Governance route constraints', function () {
    it('routes /governance/{country}/trend to the trend action instead of the year-based show action', function () {
        // whereNumber('year') on the show route ensures "trend" cannot match {year}.
        // If routing is correct → 200 with trend data; if broken → 404 or type error.
        $response = $this->getJson("/api/v1/governance/{$this->country->id}/trend");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    });
});
