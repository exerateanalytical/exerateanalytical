<?php

namespace Database\Factories;

use App\Models\BudgetAllocation;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetAllocationFactory extends Factory
{
    protected $model = BudgetAllocation::class;

    public function definition(): array
    {
        $allocated = $this->faker->randomFloat(2, 100_000, 50_000_000_000);

        return [
            'country_id'       => Country::factory(),
            'region_id'        => null,
            'year'             => $this->faker->numberBetween(2000, 2025),
            'sector_name'      => $this->faker->randomElement(['Education', 'Health', 'Infrastructure', 'Defense', 'Agriculture']),
            'allocated_amount' => $allocated,
            'executed_amount'  => $this->faker->randomFloat(2, 0, $allocated),
            'source_title'     => $this->faker->sentence(4),
            'source_url'       => $this->faker->url(),
        ];
    }
}
