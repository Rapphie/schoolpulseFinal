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
        $student = \App\Models\Student::inRandomOrder()->first();
        $subject = \App\Models\Subject::inRandomOrder()->first();
        $teacher = \App\Models\Teacher::inRandomOrder()->first();

        return [
            'student_id' => $student,
            'subject_id' => $subject,
            'grade' => $this->faker->numberBetween(75, 100),
            'quarter' => $this->faker->numberBetween(1, 4),
            'school_year' => '2024-2025',
            'teacher_id' => $teacher,
        ];
    }
}
