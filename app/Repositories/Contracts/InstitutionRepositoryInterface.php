<?php

namespace App\Repositories\Contracts;

use App\DTOs\Institution\InstitutionDTO;
use App\Models\Institution;
use Illuminate\Pagination\LengthAwarePaginator;

interface InstitutionRepositoryInterface
{
    public function allByCountry(string $countryId, array $filters = []): LengthAwarePaginator;

    public function findById(string $id): ?Institution;

    public function create(InstitutionDTO $dto): Institution;

    public function update(Institution $institution, InstitutionDTO $dto): Institution;

    public function delete(Institution $institution): bool;
}
