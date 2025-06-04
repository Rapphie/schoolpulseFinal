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
        $section = \App\Models\Section::inRandomOrder()->first() ?? \App\Models\Section::factory()->create();
        $subject = \App\Models\Subject::inRandomOrder()->first();
        $teacher = \App\Models\Teacher::inRandomOrder()->first();
        return [
            'subject_id' => $subject,
            'section_id' => $section,
            'category_name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'teacher_id' => $teacher,
        ];
    }
}
