<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Pillar;
use Illuminate\Database\Eloquent\Factories\Factory;

class PillarFactory extends Factory
{
    protected $model = Pillar::class;

    public function definition(): array
    {
        return [
            'country_id'  => Country::factory(),
            'name'        => $this->faker->words(3, true) . ' Pillar',
            'description' => $this->faker->sentence(),
            'weight'      => 100.00,
            'is_active'   => true,
            'version'     => 1,
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
