<?php

namespace Database\Factories;

use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Question;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Single-choice question with four options (the first one correct).
 *
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
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
            'type' => QuestionType::SingleChoice,
            'body' => fake()->sentence().'?',
            'default_points' => 1,
        ];
    }

    public function by(User $author): static
    {
        return $this->state(fn (array $attributes) => ['school_id' => $author->school_id, 'author_id' => $author->id]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Question $question): void {
            if ($question->options()->exists() || $question->type !== QuestionType::SingleChoice) {
                return;
            }

            foreach (['Správna', 'Nesprávna A', 'Nesprávna B', 'Nesprávna C'] as $position => $body) {
                $question->options()->create(['body' => $body, 'is_correct' => $position === 0, 'position' => $position]);
            }
        });
    }
}
