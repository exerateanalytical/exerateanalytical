<?php

namespace App\DTOs\Region;

readonly class RegionDTO
{
    public function __construct(
        public string $countryId,
        public string $name,
        public int $administrativeLevel,
        public int $population,
        public float $areaKm2,
        public ?string $parentRegionId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            countryId: $data['country_id'],
            name: $data['name'],
            administrativeLevel: $data['administrative_level'] ?? 1,
            population: $data['population'] ?? 0,
            areaKm2: $data['area_km2'] ?? 0,
            parentRegionId: $data['parent_region_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'country_id' => $this->countryId,
            'name' => $this->name,
            'administrative_level' => $this->administrativeLevel,
            'population' => $this->population,
            'area_km2' => $this->areaKm2,
            'parent_region_id' => $this->parentRegionId,
        ];
    }
}
