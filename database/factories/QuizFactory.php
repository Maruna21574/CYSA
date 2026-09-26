<?php

namespace Database\Factories;

use App\Enums\QuizPurpose;
use App\Enums\QuizStatus;
use App\Models\Course;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Draft practice quiz belonging to a course; author and school are taken from the course.
 *
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'school_id' => fn (array $attributes) => Course::find($attributes['course_id'])->school_id,
            'author_id' => fn (array $attributes) => Course::find($attributes['course_id'])->author_id,
            'title' => 'Test '.fake()->unique()->words(2, true),
            'purpose' => QuizPurpose::Practice,
            'status' => QuizStatus::Draft,
            'pass_percentage' => 60,
        ];
    }

    public function forCourse(Course $course): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => $course->id,
            'school_id' => $course->school_id,
            'author_id' => $course->author_id,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => ['status' => QuizStatus::Published, 'published_at' => now()]);
    }
}
