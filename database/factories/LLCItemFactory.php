<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LLCItem>
 */
class LLCItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "llc_id" => $this->faker->numberBetween(1, 10),
            "user_id" => $this->faker->numberBetween(1, 10),
        ];
    }
}
