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
            'subject_id' => \App\Models\Subject::factory(),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'teacher_id' => \App\Models\Teacher::factory(),
        ];
    }
}
