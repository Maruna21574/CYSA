<?php

namespace Database\Factories;

use App\Enums\SchoolType;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Stredná škola '.fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => SchoolType::Secondary,
            'city' => fake()->city(),
            'settings' => [],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
