<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random teacher user or create one if needed
        $userId = User::where('role_id', 2)->inRandomOrder()->first()?->id
            ?? User::factory()->teacher()->create()->id;
        return [
            'user_id' => $userId,
            'phone' => '09' . $this->faker->numerify('#########'),
            'gender' => $this->faker->randomElement(['male', 'female', 'other']),
            'date_of_birth' => $this->faker->dateTimeBetween('-60 years', '-25 years'),
            'address' => $this->faker->address(),
            'qualification' => $this->faker->randomElement([
                'Bachelor of Education',
                'Master of Education',
                'Bachelor of Science in Education',
                'Bachelor of Arts in Education',
                'Doctor of Education',
                'Bachelor of Elementary Education',
                'Bachelor of Secondary Education'
            ]),
            'status' => $this->faker->randomElement(['active', 'on-leave', 'inactive']),
        ];
    }

    /**
     * Configure the model as active.
     */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Configure the model as on leave.
     */
    public function onLeave(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'on-leave',
            ];
        });
    }

    /**
     * Configure the model as inactive.
     */
    public function inactive(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'inactive',
            ];
        });
    }
}
