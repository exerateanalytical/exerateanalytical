<?php

namespace App\Services\Country;

use App\DTOs\Country\CountryDTO;
use App\Exceptions\DataIntegrityException;
use App\Exceptions\UnauthorizedCountryAccessException;
use App\Models\Country;
use App\Repositories\Contracts\CountryRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CountryService
{
    public function __construct(
        private readonly CountryRepositoryInterface $repository,
        private readonly AuditService $auditService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->all($filters);
    }

    public function find(string $id): Country
    {
        $country = $this->repository->findById($id);

        if (!$country) {
            throw new DataIntegrityException("Country with ID [{$id}] not found.");
        }

        return $country;
    }

    public function create(CountryDTO $dto): Country
    {
        $existing = $this->repository->findByIsoCode($dto->isoCode);
        if ($existing) {
            throw new DataIntegrityException("A country with ISO code [{$dto->isoCode}] already exists.");
        }

        $country = $this->repository->create($dto);

        $this->auditService->log('created', $country);

        $this->clearCountryCache($country->id);

        Log::info('Country created', ['id' => $country->id, 'iso_code' => $country->iso_code]);

        return $country;
    }

    public function update(Country $country, CountryDTO $dto): Country
    {
        if ($dto->isoCode !== $country->iso_code) {
            $existing = $this->repository->findByIsoCode($dto->isoCode);
            if ($existing && $existing->id !== $country->id) {
                throw new DataIntegrityException("A country with ISO code [{$dto->isoCode}] already exists.");
            }
        }

        $oldData = $country->toArray();
        $updated = $this->repository->update($country, $dto);

        $this->auditService->log('updated', $updated, $oldData);

        $this->clearCountryCache($country->id);

        return $updated;
    }

    public function delete(Country $country): bool
    {
        $this->auditService->log('deleted', $country);
        $result = $this->repository->delete($country);
        $this->clearCountryCache($country->id);

        return $result;
    }

    public function activeCountries(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('countries:active', 86400, function () {
            return $this->repository->activeCountries();
        });
    }

    private function clearCountryCache(string $countryId): void
    {
        Cache::forget("country:{$countryId}");
        Cache::forget('countries:active');
    }
}
