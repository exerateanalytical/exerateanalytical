<?php

namespace App\DTOs\Country;

use App\Enums\RiskTier;

readonly class CountryDTO
{
    public function __construct(
        public string $name,
        public string $isoCode,
        public string $continentRegion,
        public RiskTier $riskTier = RiskTier::Moderate,
        public bool $isActive = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            isoCode: $data['iso_code'],
            continentRegion: $data['continent_region'],
            riskTier: RiskTier::from($data['risk_tier'] ?? RiskTier::Moderate->value),
            isActive: $data['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'iso_code' => $this->isoCode,
            'continent_region' => $this->continentRegion,
            'risk_tier' => $this->riskTier->value,
            'is_active' => $this->isActive,
        ];
    }
}
