<?php

namespace App\Repositories\Contracts;

use App\DTOs\Country\CountryDTO;
use App\Models\Country;
use Illuminate\Pagination\LengthAwarePaginator;

interface CountryRepositoryInterface
{
    public function all(array $filters = []): LengthAwarePaginator;

    public function findById(string $id): ?Country;

    public function findByIsoCode(string $isoCode): ?Country;

    public function create(CountryDTO $dto): Country;

    public function update(Country $country, CountryDTO $dto): Country;

    public function delete(Country $country): bool;

    public function activeCountries(): \Illuminate\Database\Eloquent\Collection;
}
