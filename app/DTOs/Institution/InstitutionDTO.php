<?php

namespace App\DTOs\Institution;

use App\Enums\InstitutionType;

readonly class InstitutionDTO
{
    public function __construct(
        public string $countryId,
        public string $name,
        public InstitutionType $type,
        public ?string $legalMandate = null,
        public float $transparencyScore = 0.00,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            countryId: $data['country_id'],
            name: $data['name'],
            type: InstitutionType::from($data['type']),
            legalMandate: $data['legal_mandate'] ?? null,
            transparencyScore: $data['transparency_score'] ?? 0.00,
        );
    }

    public function toArray(): array
    {
        return [
            'country_id' => $this->countryId,
            'name' => $this->name,
            'type' => $this->type->value,
            'legal_mandate' => $this->legalMandate,
            'transparency_score' => $this->transparencyScore,
        ];
    }
}
