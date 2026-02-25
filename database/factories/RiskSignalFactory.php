<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\RiskSignal;
use Illuminate\Database\Eloquent\Factories\Factory;

class RiskSignalFactory extends Factory
{
    protected $model = RiskSignal::class;

    public function definition(): array
    {
        return [
            'country_id'   => Country::factory(),
            'signal_type'  => 'governance_decline',
            'severity'     => 'moderate',
            'description'  => $this->faker->sentence(),
            'module'       => 'governance',
            'metadata'     => null,
            'is_escalated' => false,
            'triggered_at' => now(),
        ];
    }

    public function escalated(): static
    {
        return $this->state(fn (array $attributes) => ['is_escalated' => true]);
    }

    public function severity(string $level): static
    {
        return $this->state(fn (array $attributes) => ['severity' => $level]);
    }
}
