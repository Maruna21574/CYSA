<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\ClassroomRole;
use App\Livewire\Classrooms\ClassroomMembers;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClassroomManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->schoolAdmin = User::factory()->schoolAdmin()->create();
    }

    public function test_school_admin_can_create_classroom(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->post(route('school.classrooms.store'), ['name' => '4.A', 'grade_level' => 4, 'school_year' => '2026/2027'])
            ->assertRedirect();

        $this->assertDatabaseHas(Classroom::class, ['name' => '4.A', 'school_id' => $this->schoolAdmin->school_id]);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::ClassroomCreated->value]);
    }

    public function test_classroom_name_is_unique_within_school_year(): void
    {
        Classroom::factory()->for($this->schoolAdmin->school)->create(['name' => '4.A', 'school_year' => '2026/2027']);

        $this->actingAs($this->schoolAdmin)
            ->post(route('school.classrooms.store'), ['name' => '4.A', 'school_year' => '2026/2027'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->schoolAdmin)
            ->post(route('school.classrooms.store'), ['name' => '4.A', 'school_year' => '2027/2028'])
            ->assertSessionHasNoErrors();
    }

    public function test_school_year_must_be_consecutive_years(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->post(route('school.classrooms.store'), ['name' => '1.B', 'school_year' => '2026/2030'])
            ->assertSessionHasErrors('school_year');
    }

    public function test_school_admin_cannot_open_classroom_of_another_school(): void
    {
        $foreign = Classroom::factory()->create();

        $this->actingAs($this->schoolAdmin)->get(route('school.classrooms.show', $foreign))->assertForbidden();
        $this->actingAs($this->schoolAdmin)->put(route('school.classrooms.update', $foreign), ['name' => 'X', 'school_year' => '2026/2027'])->assertForbidden();
        $this->actingAs($this->schoolAdmin)->delete(route('school.classrooms.destroy', $foreign))->assertForbidden();
    }

    public function test_classroom_list_shows_only_own_school(): void
    {
        Classroom::factory()->for($this->schoolAdmin->school)->create(['name' => 'Moja trieda']);
        Classroom::factory()->create(['name' => 'Cudzia trieda']);

        $this->actingAs($this->schoolAdmin)
            ->get(route('school.classrooms.index'))
            ->assertOk()
            ->assertSee('Moja trieda')
            ->assertDontSee('Cudzia trieda');
    }

    public function test_members_can_be_added_and_removed(): void
    {
        $classroom = Classroom::factory()->for($this->schoolAdmin->school)->create();
        $student = User::factory()->student()->for($this->schoolAdmin->school)->create();
        $teacher = User::factory()->teacher()->for($this->schoolAdmin->school)->create();

        $component = Livewire::actingAs($this->schoolAdmin)
            ->test(ClassroomMembers::class, ['classroom' => $classroom])
            ->call('add', $student->id)
            ->set('addRole', 'teacher')
            ->call('add', $teacher->id);

        $this->assertSame(ClassroomRole::Student->value, $classroom->members()->find($student->id)->pivot->role);
        $this->assertSame(ClassroomRole::Teacher->value, $classroom->members()->find($teacher->id)->pivot->role);

        $component->call('remove', $student->id);

        $this->assertFalse($classroom->members()->whereKey($student->id)->exists());
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::ClassroomMemberRemoved->value]);
    }

    public function test_student_of_another_school_cannot_be_added(): void
    {
        $classroom = Classroom::factory()->for($this->schoolAdmin->school)->create();
        $foreignStudent = User::factory()->student()->create();

        Livewire::actingAs($this->schoolAdmin)
            ->test(ClassroomMembers::class, ['classroom' => $classroom])
            ->call('add', $foreignStudent->id)
            ->assertNotFound();

        $this->assertSame(0, $classroom->members()->count());
    }

    public function test_teacher_can_view_but_not_manage_own_classroom(): void
    {
        $classroom = Classroom::factory()->for($this->schoolAdmin->school)->create();
        $teacher = User::factory()->teacher()->for($this->schoolAdmin->school)->create();
        $student = User::factory()->student()->for($this->schoolAdmin->school)->create();
        $classroom->members()->attach($teacher->id, ['role' => ClassroomRole::Teacher->value]);

        Livewire::actingAs($teacher)
            ->test(ClassroomMembers::class, ['classroom' => $classroom])
            ->assertOk()
            ->call('add', $student->id)
            ->assertForbidden();
    }
}
