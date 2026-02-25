<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Indicator;
use App\Models\Pillar;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndicatorFactory extends Factory
{
    protected $model = Indicator::class;

    public function definition(): array
    {
        return [
            'pillar_id'            => Pillar::factory(),
            'country_id'           => Country::factory(),
            'name'                 => $this->faker->words(3, true) . ' Indicator',
            'description'          => $this->faker->sentence(),
            'unit'                 => null,
            'weight'               => 100.00,
            'normalization_method' => 'minmax',
            'data_type'            => 'numeric',
            'reliability_level'    => 'moderate',
            'reporting_lag_months' => 0,
            'is_active'            => true,
            'version'              => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function version(int $v): static
    {
        return $this->state(fn (array $attributes) => ['version' => $v]);
    }
}
