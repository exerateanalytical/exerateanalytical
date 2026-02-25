<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\GovernanceScore;
use Illuminate\Database\Eloquent\Factories\Factory;

class GovernanceScoreFactory extends Factory
{
    protected $model = GovernanceScore::class;

    public function definition(): array
    {
        return [
            'country_id'      => Country::factory(),
            'region_id'       => null,
            'year'            => 2024,
            'pillar_scores'   => [],
            'composite_score' => $this->faker->randomFloat(2, 10, 90),
            'version'         => 1,
            'calculated_at'   => now(),
            'calculated_by'   => null,
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

    public function version(int $v): static
    {
        return $this->state(fn (array $attributes) => ['version' => $v]);
    }
}
