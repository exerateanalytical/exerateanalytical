<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\RevenueRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class RevenueRecordFactory extends Factory
{
    protected $model = RevenueRecord::class;

    public function definition(): array
    {
        return [
            'country_id'           => Country::factory(),
            'year'                 => $this->faker->numberBetween(2000, 2025),
            'total_revenue'        => $this->faker->randomFloat(2, 1_000_000, 200_000_000_000),
            'tax_revenue'          => null,
            'non_tax_revenue'      => null,
            'grants'               => null,
            'revenue_to_gdp_ratio' => null,
            'source_title'         => $this->faker->sentence(4),
            'source_url'           => $this->faker->url(),
        ];
    }
}
