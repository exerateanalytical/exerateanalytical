<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Indicator;
use App\Models\IndicatorValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class IndicatorValueFactory extends Factory
{
    protected $model = IndicatorValue::class;

    public function definition(): array
    {
        return [
            'indicator_id'     => Indicator::factory(),
            'country_id'       => Country::factory(),
            'region_id'        => null,
            'year'             => 2024,
            'raw_value'        => $this->faker->randomFloat(6, 1, 100),
            'normalized_value' => null,
            'source_title'     => $this->faker->company() . ' Statistical Report',
            'source_url'       => $this->faker->url(),
            'source_document'  => null,
            'data_version'     => 1,
            'created_by'       => null,
        ];
    }

    public function national(): static
    {
        return $this->state(fn (array $attributes) => ['region_id' => null]);
    }

    public function regional(string $regionId): static
    {
        return $this->state(fn (array $attributes) => ['region_id' => $regionId]);
    }

    public function normalized(): static
    {
        return $this->state(fn (array $attributes) => [
            'normalized_value' => $this->faker->randomFloat(6, 0, 100),
        ]);
    }

    public function version(int $v): static
    {
        return $this->state(fn (array $attributes) => ['data_version' => $v]);
    }
}
