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
            "llc_id" => \App\Models\LLC::factory(),
            "teacher_id" => \App\Models\Teacher::factory(),
            "content" => $this->faker->paragraph(),
            "type" => $this->faker->randomElement(['text', 'image', 'file', 'link']),
            "order" => $this->faker->numberBetween(1, 20),
        ];
    }
}
