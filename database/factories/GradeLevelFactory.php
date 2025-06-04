<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GradeLevel>
 */
class GradeLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    /**
     * The current sequence for grade levels
     */
    protected static $sequence = 0;

    public function definition(): array
    {
        // Reset sequence when it goes beyond 6
        if (static::$sequence >= 6) {
            static::$sequence = 0;
        }

        // Increment sequence to get next grade level (1 to 6)
        static::$sequence++;
        $level = static::$sequence;

        return [
            'name' => 'Grade ' . $level,
            'level' => $level,
            'description' => fake()->sentence(),
        ];
    }

    /**
     * Configure the factory to create a specific grade level (1-6)
     *
     * @param int $level
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function level(int $level): Factory
    {
        return $this->state(function (array $attributes) use ($level) {
            return [
                'name' => 'Grade ' . $level,
                'level' => $level,
            ];
        });
    }
}
