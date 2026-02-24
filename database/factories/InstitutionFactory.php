<?php

namespace Database\Factories;

use App\Enums\InstitutionType;
use App\Models\Country;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => $this->faker->company() . ' ' . $this->faker->randomElement(['Ministry', 'Agency', 'Department']),
            'type' => $this->faker->randomElement(InstitutionType::cases())->value,
            'legal_mandate' => $this->faker->paragraph(),
            'transparency_score' => $this->faker->randomFloat(2, 0, 100),
        ];
    }
}
