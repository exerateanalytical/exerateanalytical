<?php

namespace App\Repositories\Contracts;

use App\DTOs\Region\RegionDTO;
use App\Models\Region;
use Illuminate\Pagination\LengthAwarePaginator;

interface RegionRepositoryInterface
{
    public function allByCountry(string $countryId, array $filters = []): LengthAwarePaginator;

    public function findById(string $id): ?Region;

    public function create(RegionDTO $dto): Region;

    public function update(Region $region, RegionDTO $dto): Region;

    public function delete(Region $region): bool;

    public function childRegions(string $parentId): \Illuminate\Database\Eloquent\Collection;
}
