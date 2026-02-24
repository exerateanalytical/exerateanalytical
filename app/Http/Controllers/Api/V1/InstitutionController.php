<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Institution\InstitutionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\StoreInstitutionRequest;
use App\Http\Requests\Institution\UpdateInstitutionRequest;
use App\Http\Resources\Institution\InstitutionResource;
use App\Services\Country\CountryService;
use App\Services\Institution\InstitutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstitutionController extends Controller
{
    public function __construct(
        private readonly InstitutionService $service,
        private readonly CountryService $countryService,
    ) {}

    public function index(Request $request, string $countryId): AnonymousResourceCollection
    {
        $this->countryService->find($countryId);

        $institutions = $this->service->listByCountry(
            $countryId,
            $request->only(['type', 'search', 'per_page'])
        );

        return InstitutionResource::collection($institutions);
    }

    public function store(StoreInstitutionRequest $request): JsonResponse
    {
        $institution = $this->service->create(InstitutionDTO::fromArray($request->validated()));

        return (new InstitutionResource($institution))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $countryId, string $id): InstitutionResource
    {
        $institution = $this->service->find($id);

        return new InstitutionResource($institution);
    }

    public function update(UpdateInstitutionRequest $request, string $countryId, string $id): InstitutionResource
    {
        $institution = $this->service->find($id);
        $data = array_merge($request->validated(), ['country_id' => $countryId]);
        $updated = $this->service->update($institution, InstitutionDTO::fromArray($data));

        return new InstitutionResource($updated);
    }

    public function destroy(string $countryId, string $id): JsonResponse
    {
        $institution = $this->service->find($id);
        $this->service->delete($institution);

        return response()->json(['status' => 'success', 'message' => 'Institution deleted.']);
    }
}
