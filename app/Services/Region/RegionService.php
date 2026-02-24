<?php

namespace App\Services\Region;

use App\DTOs\Region\RegionDTO;
use App\Exceptions\DataIntegrityException;
use App\Models\Region;
use App\Repositories\Contracts\RegionRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Pagination\LengthAwarePaginator;

class RegionService
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
        private readonly AuditService $auditService,
    ) {}

    public function listByCountry(string $countryId, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->allByCountry($countryId, $filters);
    }

    public function find(string $id): Region
    {
        $region = $this->repository->findById($id);

        if (!$region) {
            throw new DataIntegrityException("Region with ID [{$id}] not found.");
        }

        return $region;
    }

    public function create(RegionDTO $dto): Region
    {
        $region = $this->repository->create($dto);
        $this->auditService->log('created', $region);

        return $region;
    }

    public function update(Region $region, RegionDTO $dto): Region
    {
        $oldData = $region->toArray();
        $updated = $this->repository->update($region, $dto);
        $this->auditService->log('updated', $updated, $oldData);

        return $updated;
    }

    public function delete(Region $region): bool
    {
        $this->auditService->log('deleted', $region);
        return $this->repository->delete($region);
    }
}
