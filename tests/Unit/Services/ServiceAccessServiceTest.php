<?php

use App\Exceptions\DataIntegrityException;
use App\Models\Country;
use App\Models\User;
use App\Services\Development\ServiceAccessService;

beforeEach(function () {
    $this->service = new ServiceAccessService();
    $this->country = Country::factory()->create();
});

describe('validatePercentFields()', function () {
    it('throws DataIntegrityException when a percent field exceeds 100', function () {
        expect(fn () => $this->service->validatePercentFields([
            'electricity_access_percent' => 110.0,
        ]))->toThrow(DataIntegrityException::class);
    });

    it('throws DataIntegrityException when a percent field is negative', function () {
        expect(fn () => $this->service->validatePercentFields([
            'internet_penetration_percent' => -5.0,
        ]))->toThrow(DataIntegrityException::class);
    });
});

describe('store()', function () {
    it('sets created_by from Auth::id()', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $record = $this->service->store([
            'country_id'   => $this->country->id,
            'year'         => 2023,
            'source_title' => 'Test Source',
            'source_url'   => 'https://example.com',
        ]);

        expect($record->created_by)->toBe($user->id);
    });

    it('ignores incoming created_by value and uses Auth::id() instead', function () {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);

        $record = $this->service->store([
            'country_id'   => $this->country->id,
            'year'         => 2023,
            'created_by'   => $other->id,
            'source_title' => 'Test Source',
            'source_url'   => 'https://example.com',
        ]);

        expect($record->created_by)->toBe($user->id)
            ->and($record->created_by)->not->toBe($other->id);
    });

    it('persists the record to the database', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->service->store([
            'country_id'   => $this->country->id,
            'year'         => 2024,
            'source_title' => 'Persistence Test',
            'source_url'   => 'https://example.com',
        ]);

        $this->assertDatabaseHas('service_access_records', [
            'country_id' => $this->country->id,
            'year'       => 2024,
        ]);
    });
});
