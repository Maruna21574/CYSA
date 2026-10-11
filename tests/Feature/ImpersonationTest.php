<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\ClassroomRole;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\User;
use App\Services\Users\Impersonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_switches_to_teacher_and_back(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($superAdmin)
            ->post(route('impersonation.start', $teacher))
            ->assertRedirect(route('teacher.dashboard'));

        $this->assertAuthenticatedAs($teacher);
        $this->assertSame($superAdmin->id, session(Impersonation::SESSION_KEY));
        $this->get(route('teacher.dashboard'))->assertOk()->assertSee('Vrátiť sa na môj účet');

        $this->post(route('impersonation.stop'))->assertRedirect(route('impersonation.index'));

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertFalse(session()->has(Impersonation::SESSION_KEY));
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::ImpersonationStarted->value, 'user_id' => $superAdmin->id, 'auditable_id' => $teacher->id]);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::ImpersonationStopped->value, 'user_id' => $superAdmin->id]);
        $this->assertNull($teacher->fresh()->last_login_at);
    }

    public function test_switching_again_keeps_the_original_account_for_returning(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $schoolAdmin = User::factory()->schoolAdmin()->create();
        $student = User::factory()->student()->for($schoolAdmin->school)->create();

        $this->actingAs($superAdmin)->post(route('impersonation.start', $schoolAdmin));
        $this->post(route('impersonation.start', $student))->assertRedirect(route('student.dashboard'));
        $this->assertAuthenticatedAs($student);

        $this->post(route('impersonation.stop'));
        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_student_can_never_switch_to_a_higher_role(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->for($student->school)->create();

        $this->actingAs($student)->get(route('impersonation.index'))->assertForbidden();
        $this->actingAs($student)->post(route('impersonation.start', $teacher))->assertForbidden();

        $this->assertAuthenticatedAs($student);
    }

    public function test_while_switched_to_a_student_only_the_original_rights_apply(): void
    {
        $schoolAdmin = User::factory()->schoolAdmin()->create();
        $student = User::factory()->student()->for($schoolAdmin->school)->create();
        $otherSchoolTeacher = User::factory()->teacher()->create();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($schoolAdmin)->post(route('impersonation.start', $student));

        // Rights of the school admin still apply, never more.
        $this->post(route('impersonation.start', $otherSchoolTeacher))->assertForbidden();
        $this->post(route('impersonation.start', $superAdmin))->assertForbidden();
        $this->assertAuthenticatedAs($student);
    }

    public function test_school_admin_switches_only_within_own_school(): void
    {
        $schoolAdmin = User::factory()->schoolAdmin()->create();
        $ownTeacher = User::factory()->teacher()->for($schoolAdmin->school)->create();
        $colleagueAdmin = User::factory()->schoolAdmin()->for($schoolAdmin->school)->create();
        $foreignStudent = User::factory()->student()->create();

        $this->actingAs($schoolAdmin)->get(route('impersonation.index'))
            ->assertOk()
            ->assertSee($ownTeacher->email)
            ->assertDontSee($colleagueAdmin->email)
            ->assertDontSee($foreignStudent->email);

        $this->post(route('impersonation.start', $colleagueAdmin))->assertForbidden();
        $this->post(route('impersonation.start', $foreignStudent))->assertForbidden();
        $this->post(route('impersonation.start', $ownTeacher))->assertRedirect(route('teacher.dashboard'));
    }

    public function test_teacher_switches_only_to_students_of_own_classrooms(): void
    {
        $teacher = User::factory()->teacher()->create();
        $classroom = Classroom::factory()->for($teacher->school)->create();
        $ownStudent = User::factory()->student()->for($teacher->school)->create();
        $otherStudent = User::factory()->student()->for($teacher->school)->create();
        $classroom->members()->attach([
            $teacher->id => ['role' => ClassroomRole::Teacher->value],
            $ownStudent->id => ['role' => ClassroomRole::Student->value],
        ]);

        $this->actingAs($teacher)->post(route('impersonation.start', $otherStudent))->assertForbidden();
        $this->actingAs($teacher)->post(route('impersonation.start', $ownStudent))->assertRedirect(route('student.dashboard'));
    }

    public function test_inactive_accounts_cannot_be_switched_to(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $inactive = User::factory()->teacher()->inactive()->create();

        $this->actingAs($superAdmin)->post(route('impersonation.start', $inactive))->assertForbidden();
    }

    public function test_password_change_and_data_export_are_blocked_while_switched(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($superAdmin)->post(route('impersonation.start', $student));

        $this->get(route('profile.export'))->assertForbidden();
        $this->put(route('profile.password'), [
            'current_password' => 'password', 'password' => 'noveHeslo123', 'password_confirmation' => 'noveHeslo123',
        ])->assertForbidden();
    }

    public function test_actions_done_while_switched_record_the_real_person(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $schoolAdmin = User::factory()->schoolAdmin()->create();
        $student = User::factory()->student()->for($schoolAdmin->school)->create();

        $this->actingAs($superAdmin)->post(route('impersonation.start', $schoolAdmin));
        $this->put(route('users.update', $student), [
            'first_name' => 'Nové', 'last_name' => $student->last_name, 'email' => $student->email, 'role' => 'student',
        ]);

        $log = AuditLog::where('action', AuditAction::UserUpdated->value)->firstOrFail();
        $this->assertSame($schoolAdmin->id, $log->user_id);
        $this->assertSame($superAdmin->id, $log->metadata['impersonated_by']);
    }
}
