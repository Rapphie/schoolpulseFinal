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
        $llc = \App\Models\LLC::inRandomOrder()->first() ?? \App\Models\LLC::factory()->create();
        $teacher = \App\Models\Teacher::inRandomOrder()->first();
        return [
            "llc_id" => $llc,
            "teacher_id" => $teacher,
        ];
    }
}
