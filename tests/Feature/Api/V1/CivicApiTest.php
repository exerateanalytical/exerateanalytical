<?php

use App\Models\ApprovalRating;
use App\Models\Country;
use App\Models\Petition;
use App\Models\RepresentationRecord;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->country = Country::factory()->create();
});

describe('GET /api/v1/civic/approval/{country}/{year}', function () {
    it('returns 200 with approval rating data when a record exists', function () {
        ApprovalRating::create([
            'country_id' => $this->country->id,
            'region_id' => null,
            'year' => 2023,
            'approve_percent' => 54.30,
            'disapprove_percent' => 32.10,
            'neutral_percent' => 13.60,
            'sample_size' => 2500,
            'calculated_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/civic/approval/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'country_id', 'year',
                    'approve_percent', 'disapprove_percent', 'neutral_percent',
                    'sample_size', 'calculated_at',
                ],
            ])
            ->assertJsonPath('data.year', 2023)
            ->assertJsonPath('data.country_id', $this->country->id);
    });

    it('returns 404 when no approval rating record exists', function () {
        $response = $this->getJson("/api/v1/civic/approval/{$this->country->id}/1975");

        $response->assertNotFound()
            ->assertJsonPath('message', 'No approval rating found.');
    });
});

describe('GET /api/v1/civic/petitions/{country}', function () {
    it('returns 200 with approved petitions', function () {
        Petition::create([
            'country_id' => $this->country->id,
            'title' => 'Build more schools',
            'description' => 'We need more schools in rural areas.',
            'category' => 'Education',
            'status' => 'approved',
            'content_hash' => hash('sha256', 'build more schools'),
        ]);

        // Pending petition should not appear
        Petition::create([
            'country_id' => $this->country->id,
            'title' => 'Fix the roads',
            'description' => 'Roads in district 5 are impassable.',
            'category' => 'Infrastructure',
            'status' => 'pending',
            'content_hash' => hash('sha256', 'fix the roads'),
        ]);

        $response = $this->getJson("/api/v1/civic/petitions/{$this->country->id}");

        $response->assertOk()
            ->assertJsonStructure(['data']);

        // Only the approved petition should be returned
        $data = $response->json('data');
        expect(count($data))->toBe(1);
    });

    it('returns 200 with empty data when no approved petitions exist', function () {
        $response = $this->getJson("/api/v1/civic/petitions/{$this->country->id}");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    });
});

describe('GET /api/v1/civic/representation/{country}/{year}', function () {
    it('returns 200 with representation record data', function () {
        RepresentationRecord::create([
            'country_id' => $this->country->id,
            'region_id' => null,
            'year' => 2023,
            'gender_distribution' => ['male' => 62, 'female' => 38],
            'regional_distribution' => ['north' => 40, 'south' => 60],
            'equity_index_score' => 58.50,
        ]);

        $response = $this->getJson("/api/v1/civic/representation/{$this->country->id}/2023");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'country_id', 'year', 'gender_distribution', 'equity_index_score'],
            ])
            ->assertJsonPath('data.year', 2023);
    });

    it('returns 404 when no representation record exists', function () {
        $response = $this->getJson("/api/v1/civic/representation/{$this->country->id}/1960");

        $response->assertNotFound()
            ->assertJsonPath('message', 'No representation record found.');
    });
});
