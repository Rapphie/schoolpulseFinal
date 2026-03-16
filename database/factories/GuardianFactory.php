<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guardian>
 */
class GuardianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get a random parent user or create one if needed
        $userId = \App\Models\User::where('role_id', 3)->inRandomOrder()->first()?->id
            ?? \App\Models\User::factory()->create(['role_id' => 3])->id;

        return [
            'user_id' => $userId,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => '09'.$this->faker->numerify('#########'),
            'relationship' => $this->faker->randomElement(['parent', 'sibling', 'relative', 'guardian']),
        ];
    }
}
