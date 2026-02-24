<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Region\RegionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Region\StoreRegionRequest;
use App\Http\Requests\Region\UpdateRegionRequest;
use App\Http\Resources\Region\RegionResource;
use App\Services\Country\CountryService;
use App\Services\Region\RegionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegionController extends Controller
{
    public function __construct(
        private readonly RegionService $service,
        private readonly CountryService $countryService,
    ) {}

    public function index(Request $request, string $countryId): AnonymousResourceCollection
    {
        $this->countryService->find($countryId);

        $regions = $this->service->listByCountry($countryId, $request->only(['search', 'per_page']));

        return RegionResource::collection($regions);
    }

    public function store(StoreRegionRequest $request): JsonResponse
    {
        $region = $this->service->create(RegionDTO::fromArray($request->validated()));

        return (new RegionResource($region))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $countryId, string $id): RegionResource
    {
        $region = $this->service->find($id);

        return new RegionResource($region);
    }

    public function update(UpdateRegionRequest $request, string $countryId, string $id): RegionResource
    {
        $region = $this->service->find($id);
        $data = array_merge($request->validated(), ['country_id' => $countryId]);
        $updated = $this->service->update($region, RegionDTO::fromArray($data));

        return new RegionResource($updated);
    }

    public function destroy(string $countryId, string $id): JsonResponse
    {
        $region = $this->service->find($id);
        $this->service->delete($region);

        return response()->json(['status' => 'success', 'message' => 'Region deleted.']);
    }
}
