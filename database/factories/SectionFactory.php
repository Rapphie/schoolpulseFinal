<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = \App\Models\Section::class;

    public function definition(): array
    {
        $gradeLevel = \App\Models\GradeLevel::inRandomOrder()->first();

        return [
            'name' => strtoupper($this->faker->randomLetter().$this->faker->randomLetter()),
            'grade_level_id' => $gradeLevel,
            'description' => $this->faker->sentence(),
            'teacher_id' => Teacher::factory(),
            'capacity' => $this->faker->numberBetween(30, 50),
        ];
    }

    public function withAdviser($userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'teacher_id' => $userId,
            ];
        });
    }
}
