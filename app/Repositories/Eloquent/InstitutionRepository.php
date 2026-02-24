<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Institution\InstitutionDTO;
use App\Models\Institution;
use App\Repositories\Contracts\InstitutionRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class InstitutionRepository implements InstitutionRepositoryInterface
{
    public function __construct(private readonly Institution $model) {}

    public function allByCountry(string $countryId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->where('country_id', $countryId);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'ilike', "%{$filters['search']}%");
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id): ?Institution
    {
        return $this->model->find($id);
    }

    public function create(InstitutionDTO $dto): Institution
    {
        return $this->model->create($dto->toArray());
    }

    public function update(Institution $institution, InstitutionDTO $dto): Institution
    {
        $institution->update($dto->toArray());
        return $institution->fresh();
    }

    public function delete(Institution $institution): bool
    {
        return $institution->delete();
    }
}
