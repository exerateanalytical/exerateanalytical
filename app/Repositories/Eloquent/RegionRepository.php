<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Region\RegionDTO;
use App\Models\Region;
use App\Repositories\Contracts\RegionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RegionRepository implements RegionRepositoryInterface
{
    public function __construct(private readonly Region $model) {}

    public function allByCountry(string $countryId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('country_id', $countryId)
            ->whereNull('parent_region_id');

        if (isset($filters['search'])) {
            $query->where('name', 'ilike', "%{$filters['search']}%");
        }

        return $query->with('childRegions')->orderBy('name')->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id): ?Region
    {
        return $this->model->find($id);
    }

    public function create(RegionDTO $dto): Region
    {
        return $this->model->create($dto->toArray());
    }

    public function update(Region $region, RegionDTO $dto): Region
    {
        $region->update($dto->toArray());
        return $region->fresh();
    }

    public function delete(Region $region): bool
    {
        return $region->delete();
    }

    public function childRegions(string $parentId): Collection
    {
        return $this->model->where('parent_region_id', $parentId)->orderBy('name')->get();
    }
}
