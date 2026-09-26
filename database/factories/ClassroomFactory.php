<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grade = fake()->numberBetween(1, 4);

        return [
            'school_id' => School::factory(),
            'name' => $grade.'.'.fake()->unique()->randomLetter(),
            'grade_level' => $grade,
            'school_year' => Classroom::currentSchoolYear(),
        ];
    }
}
