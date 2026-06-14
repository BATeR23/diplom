<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'phone' => fake()->numerify('+48#########'),
            'role' => fake()->randomElement(['driver', 'passenger']),
            'avatar_url' => fake()->imageUrl(200, 200, 'people'),
            'bio' => fake()->sentence(),
            'rating_average' => fake()->randomFloat(2, 4, 5),
            'rides_completed' => fake()->numberBetween(0, 200),
            'preferences' => [
                'music' => fake()->randomElement(['off', 'quiet', 'any']),
                'chat' => fake()->randomElement(['silent', 'light', 'talkative']),
            ],
            'remember_token' => Str::random(10),
        ];
    }
}
