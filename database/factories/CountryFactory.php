<?php

namespace Database\Factories;

use App\Enums\RiskTier;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->country(),
            'iso_code' => strtoupper($this->faker->unique()->lexify('???')),
            'continent_region' => $this->faker->randomElement([
                'West Africa', 'East Africa', 'Central Africa',
                'North Africa', 'Southern Africa', 'Horn of Africa',
            ]),
            'risk_tier' => $this->faker->randomElement(RiskTier::cases())->value,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => ['risk_tier' => RiskTier::High->value]);
    }
}
