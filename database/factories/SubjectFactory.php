<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'description' => $this->faker->sentence(),
            'units' => $this->faker->numberBetween(1, 4),
            'hours_per_week' => $this->faker->numberBetween(1, 6),
            'is_active' => true,
        ];
    }
}
