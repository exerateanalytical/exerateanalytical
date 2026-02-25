<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\FiscalRiskSignal;
use Illuminate\Database\Eloquent\Factories\Factory;

class FiscalRiskSignalFactory extends Factory
{
    protected $model = FiscalRiskSignal::class;

    public function definition(): array
    {
        return [
            'country_id'   => Country::factory(),
            'year'         => $this->faker->numberBetween(2000, 2025),
            'risk_type'    => $this->faker->randomElement(['debt_sustainability', 'budget_execution', 'revenue_instability']),
            'severity'     => $this->faker->randomElement(['low', 'moderate', 'high', 'critical']),
            'description'  => $this->faker->sentence(),
            'triggered_at' => now(),
        ];
    }
}
