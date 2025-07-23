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
        $student = \App\Models\Student::inRandomOrder()->first();
        $subject = \App\Models\Subject::inRandomOrder()->first();
        $teacher = \App\Models\Teacher::inRandomOrder()->first();


        return [
            'student_id' => $student,
            'subject_id' => $subject,
            'status' => $this->faker->randomElement(['present', 'absent', 'late', 'excused']),
            'date' => $this->faker->dateTimeBetween('-2 months', 'now'),
            'quarter' => $this->faker->numberBetween(1, 4),
            'school_year' => '2024-2025',
            'time_in' => $this->faker->dateTimeBetween('08:00:00', '08:30:00'),
            // 'time_out' => $this->faker->dateTimeBetween('15:00:00', '17:00:00'),
            'teacher_id' =>  $teacher, // Teacher role
        ];
    }
}
