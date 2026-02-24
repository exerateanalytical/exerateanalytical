<?php

use App\DTOs\Country\CountryDTO;
use App\Enums\RiskTier;
use App\Exceptions\DataIntegrityException;
use App\Models\Country;
use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Services\AuditService;
use App\Services\Country\CountryService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->repository = Mockery::mock(CountryRepositoryInterface::class);
    $this->auditService = Mockery::mock(AuditService::class);
    $this->service = new CountryService($this->repository, $this->auditService);
});

it('throws DataIntegrityException when creating a country with a duplicate ISO code', function () {
    $dto = new CountryDTO(
        name: 'Nigeria',
        isoCode: 'NGA',
        continentRegion: 'West Africa',
        riskTier: RiskTier::Low,
    );

    $existingCountry = Mockery::mock(Country::class);
    $this->repository->shouldReceive('findByIsoCode')->with('NGA')->andReturn($existingCountry);

    expect(fn () => $this->service->create($dto))
        ->toThrow(DataIntegrityException::class);
});

it('creates a country when ISO code is unique', function () {
    $dto = new CountryDTO(
        name: 'Ghana',
        isoCode: 'GHA',
        continentRegion: 'West Africa',
        riskTier: RiskTier::Low,
    );

    $country = Mockery::mock(Country::class);
    $country->shouldReceive('toArray')->andReturn(['id' => 'uuid-1']);
    $country->shouldReceive('getAttribute')->with('id')->andReturn('uuid-1');
    $country->shouldReceive('getAttribute')->with('iso_code')->andReturn('GHA');

    $this->repository->shouldReceive('findByIsoCode')->with('GHA')->andReturn(null);
    $this->repository->shouldReceive('create')->with($dto)->andReturn($country);
    $this->auditService->shouldReceive('log')->with('created', $country);

    Cache::shouldReceive('forget')->twice();

    $result = $this->service->create($dto);

    expect($result)->toBe($country);
});
