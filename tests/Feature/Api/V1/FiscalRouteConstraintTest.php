<?php

use App\Models\Country;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->country = Country::factory()->create();
});

describe('Fiscal route year constraints', function () {
    it('returns 404 for non-numeric year on debt endpoint', function () {
        $this->getJson("/api/v1/fiscal/debt/{$this->country->id}/abc")
            ->assertNotFound();
    });

    it('returns 404 for non-numeric year on budget endpoint', function () {
        $this->getJson("/api/v1/fiscal/budget/{$this->country->id}/abc")
            ->assertNotFound();
    });

    it('returns 404 for non-numeric year on revenue endpoint', function () {
        $this->getJson("/api/v1/fiscal/revenue/{$this->country->id}/abc")
            ->assertNotFound();
    });

    it('returns 404 for non-numeric year on risk endpoint', function () {
        $this->getJson("/api/v1/fiscal/risk/{$this->country->id}/abc")
            ->assertNotFound();
    });
});
