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
        static $usedCombinations = [];

        do {
            $studentId = $this->faker->numberBetween(1, 10);
            $subjectId = $this->faker->numberBetween(1, 10);
        } while (in_array([$studentId, $subjectId], $usedCombinations));

        $usedCombinations[] = [$studentId, $subjectId];

        return [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'grade' => $this->faker->randomFloat(2, 0, 100),
            'user_id' => $this->faker->numberBetween(1, 10),
        ];
    }
}
