<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => fn () => Module::create([
                'course_id' => Course::factory()->create()->id,
                'title' => 'Modul',
                'position' => 0,
            ])->id,
            'course_id' => fn (array $attributes) => Module::find($attributes['module_id'])->course_id,
            'title' => 'Kapitola '.fake()->unique()->words(2, true),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'position' => 0,
            'is_published' => true,
        ];
    }

    public function forCourse(Course $course): static
    {
        return $this->state(function (array $attributes) use ($course): array {
            $module = $course->modules()->first() ?? $course->modules()->create(['title' => 'Modul', 'position' => 0]);

            return ['course_id' => $course->id, 'module_id' => $module->id];
        });
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['is_published' => false]);
    }
}
