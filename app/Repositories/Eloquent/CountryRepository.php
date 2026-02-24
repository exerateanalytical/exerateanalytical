<?php

namespace App\Repositories\Eloquent;

use App\DTOs\Country\CountryDTO;
use App\Models\Country;
use App\Repositories\Contracts\CountryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CountryRepository implements CountryRepositoryInterface
{
    public function __construct(private readonly Country $model) {}

    public function all(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['risk_tier'])) {
            $query->where('risk_tier', $filters['risk_tier']);
        }

        if (isset($filters['continent_region'])) {
            $query->where('continent_region', $filters['continent_region']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'ilike', "%{$filters['search']}%")
                  ->orWhere('iso_code', 'ilike', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 15);
    }

    public function findById(string $id): ?Country
    {
        return $this->model->find($id);
    }

    public function findByIsoCode(string $isoCode): ?Country
    {
        return $this->model->where('iso_code', $isoCode)->first();
    }

    public function create(CountryDTO $dto): Country
    {
        return $this->model->create($dto->toArray());
    }

    public function update(Country $country, CountryDTO $dto): Country
    {
        $country->update($dto->toArray());
        return $country->fresh();
    }

    public function delete(Country $country): bool
    {
        return $country->delete();
    }

    public function activeCountries(): Collection
    {
        return $this->model->active()->orderBy('name')->get();
    }
}
