<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke test: every administration page renders for the role that owns it.
 */
class AdminPagesRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_pages_render(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $school = School::factory()->create();
        $user = User::factory()->for($school)->create();

        foreach ([
            route('admin.schools.index'),
            route('admin.schools.create'),
            route('admin.schools.edit', $school),
            route('users.index'),
            route('users.create'),
            route('users.edit', $user),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_school_admin_pages_render(): void
    {
        $schoolAdmin = User::factory()->schoolAdmin()->create();
        $classroom = Classroom::factory()->for($schoolAdmin->school)->create();
        $student = User::factory()->student()->for($schoolAdmin->school)->create();

        foreach ([
            route('users.index'),
            route('users.create'),
            route('users.edit', $student),
            route('school.classrooms.index'),
            route('school.classrooms.create'),
            route('school.classrooms.show', $classroom),
            route('school.classrooms.edit', $classroom),
            route('school.students.import'),
        ] as $url) {
            $this->actingAs($schoolAdmin)->get($url)->assertOk();
        }
    }
}
