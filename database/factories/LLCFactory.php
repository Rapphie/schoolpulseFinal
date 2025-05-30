<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LLC>
 */
class LLCFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => $this->faker->numberBetween(1, 10),
            'title' => $this->faker->word,
            'description' => $this->faker->sentence(),
            'user_id' => $this->faker->numberBetween(1, 10),
        ];
    }
}
