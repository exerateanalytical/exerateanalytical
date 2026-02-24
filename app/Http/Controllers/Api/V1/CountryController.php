<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Country\CountryDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Country\StoreCountryRequest;
use App\Http\Requests\Country\UpdateCountryRequest;
use App\Http\Resources\Country\CountryCollection;
use App\Http\Resources\Country\CountryResource;
use App\Services\Country\CountryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function __construct(private readonly CountryService $service)
    {
        $this->authorizeResource(\App\Models\Country::class, 'country');
    }

    public function index(Request $request): CountryCollection
    {
        $countries = $this->service->list($request->only([
            'risk_tier', 'continent_region', 'is_active', 'search', 'per_page',
        ]));

        return new CountryCollection($countries);
    }

    public function store(StoreCountryRequest $request): JsonResponse
    {
        $country = $this->service->create(CountryDTO::fromArray($request->validated()));

        return (new CountryResource($country))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $id): CountryResource
    {
        $country = $this->service->find($id);

        return new CountryResource($country);
    }

    public function update(UpdateCountryRequest $request, string $id): CountryResource
    {
        $country = $this->service->find($id);
        $updated = $this->service->update($country, CountryDTO::fromArray($request->validated()));

        return new CountryResource($updated);
    }

    public function destroy(string $id): JsonResponse
    {
        $country = $this->service->find($id);
        $this->service->delete($country);

        return response()->json(['status' => 'success', 'message' => 'Country deleted.']);
    }
}
