<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Grade>
 */
class GradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => \App\Models\Student::factory(),
            'subject_id' => \App\Models\Subject::factory(),
            'grade' => $this->faker->numberBetween(75, 100),
            'max_score' => 100,
            'assessment_type' => $this->faker->randomElement(['quiz', 'exam', 'project', 'assignment']),
            'assessment_name' => $this->faker->words(3, true),
            'quarter' => $this->faker->numberBetween(1, 4),
            'school_year' => '2024-2025',
            'assessment_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'teacher_id' => \App\Models\Teacher::factory(),,
        ];
    }
}
