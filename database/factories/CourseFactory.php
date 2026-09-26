<?php

namespace Database\Factories;

use App\Enums\CourseStatus;
use App\Enums\Difficulty;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Draft course whose author is a teacher of the same school.
 *
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'author_id' => fn (array $attributes) => User::factory()->create([
                'school_id' => $attributes['school_id'],
                'role' => UserRole::Teacher,
            ])->id,
            'title' => 'Kurz '.fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'difficulty' => Difficulty::Beginner,
            'status' => CourseStatus::Draft,
            'sequential_chapters' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['status' => CourseStatus::Published, 'published_at' => now()]);
    }

    public function by(User $author): static
    {
        return $this->state(fn (array $attributes) => ['school_id' => $author->school_id, 'author_id' => $author->id]);
    }
}
