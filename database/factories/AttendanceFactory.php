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
            'student_id' => $this->faker->numberBetween(1, 10),
            'subject_id' => $this->faker->numberBetween(1, 10),
            'remarks' => $this->faker->word(),
            'user_id' => $this->faker->numberBetween(1, 10),

        ];
    }
}
