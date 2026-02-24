<?php

namespace App\Services\Institution;

use App\DTOs\Institution\InstitutionDTO;
use App\Exceptions\DataIntegrityException;
use App\Models\Institution;
use App\Repositories\Contracts\InstitutionRepositoryInterface;
use App\Services\AuditService;
use Illuminate\Pagination\LengthAwarePaginator;

class InstitutionService
{
    public function __construct(
        private readonly InstitutionRepositoryInterface $repository,
        private readonly AuditService $auditService,
    ) {}

    public function listByCountry(string $countryId, array $filters = []): LengthAwarePaginator
    {
        return $this->repository->allByCountry($countryId, $filters);
    }

    public function find(string $id): Institution
    {
        $institution = $this->repository->findById($id);

        if (!$institution) {
            throw new DataIntegrityException("Institution with ID [{$id}] not found.");
        }

        return $institution;
    }

    public function create(InstitutionDTO $dto): Institution
    {
        $institution = $this->repository->create($dto);
        $this->auditService->log('created', $institution);

        return $institution;
    }

    public function update(Institution $institution, InstitutionDTO $dto): Institution
    {
        $oldData = $institution->toArray();
        $updated = $this->repository->update($institution, $dto);
        $this->auditService->log('updated', $updated, $oldData);

        return $updated;
    }

    public function delete(Institution $institution): bool
    {
        $this->auditService->log('deleted', $institution);
        return $this->repository->delete($institution);
    }
}
