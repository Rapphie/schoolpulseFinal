<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
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
            'status' => $this->faker->randomElement(['present', 'absent', 'late', 'excused']),
            'date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'quarter' => $this->faker->numberBetween(1, 4),
            'school_year' => '2024-2025',
            'time_in' => $this->faker->dateTimeBetween('08:00:00', '08:30:00'),
            'time_out' => $this->faker->dateTimeBetween('15:00:00', '17:00:00'),
            'remarks' => $this->faker->optional(0.3)->sentence(),
            'teacher_id' => \App\Models\Teacher::factory(),, // Teacher role
        ];
    }
}
