<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = \App\Models\Section::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper($this->faker->randomLetter() . $this->faker->randomLetter()),
            'grade_level' => $this->faker->numberBetween(7, 12),
            'description' => $this->faker->sentence(),
            'teacher_id' => Teacher::factory(),
            'capacity' => $this->faker->numberBetween(30, 50),
        ];
    }

    public function withAdviser($userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'adviser_id' => $userId,
            ];
        });
    }
}
