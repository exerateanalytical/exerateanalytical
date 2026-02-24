<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => $this->faker->city() . ' Region',
            'administrative_level' => $this->faker->numberBetween(1, 4),
            'population' => $this->faker->numberBetween(10000, 5000000),
            'area_km2' => $this->faker->randomFloat(4, 100, 500000),
            'parent_region_id' => null,
        ];
    }
}
