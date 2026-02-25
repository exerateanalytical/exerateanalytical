<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\NationalDebtRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class NationalDebtRecordFactory extends Factory
{
    protected $model = NationalDebtRecord::class;

    public function definition(): array
    {
        return [
            'country_id'                 => Country::factory(),
            'year'                       => $this->faker->numberBetween(2000, 2025),
            'total_debt'                 => $this->faker->randomFloat(2, 1_000_000, 500_000_000_000),
            'debt_to_gdp_ratio'          => $this->faker->randomFloat(2, 10, 120),
            'external_debt'              => null,
            'domestic_debt'              => null,
            'debt_service_total'         => null,
            'debt_service_ratio'         => null,
            'interest_payments'          => null,
            'interest_as_budget_percent' => null,
            'source_title'               => $this->faker->sentence(4),
            'source_url'                 => $this->faker->url(),
            'data_version'               => 1,
            'created_by'                 => null,
        ];
    }
}
