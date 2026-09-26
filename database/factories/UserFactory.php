<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Defaults to a student of a new school. Use the role states for other roles.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Student,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::SuperAdmin, 'school_id' => null]);
    }

    public function schoolAdmin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::SchoolAdmin]);
    }

    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Teacher]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => ['role' => UserRole::Student]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
