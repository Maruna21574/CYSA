<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Users\UserTable;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->schoolAdmin = User::factory()->schoolAdmin()->create();
    }

    public function test_school_admin_creates_student_in_own_school_with_invitation(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->post(route('users.store'), [
                'first_name' => 'Jana',
                'last_name' => 'Kováčová',
                'email' => 'Jana@Example.com',
                'role' => UserRole::Student->value,
            ])
            ->assertRedirect(route('users.index'));

        $student = User::where('email', 'jana@example.com')->firstOrFail();

        $this->assertSame($this->schoolAdmin->school_id, $student->school_id);
        $this->assertSame(UserRole::Student, $student->role);
        Notification::assertSentTo($student, AccountInvitation::class);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::UserCreated->value, 'auditable_id' => $student->id]);
    }

    public function test_user_created_with_password_is_not_invited_by_default(): void
    {
        $this->actingAs($this->schoolAdmin)->post(route('users.store'), [
            'first_name' => 'Peter',
            'last_name' => 'Novák',
            'email' => 'peter@example.com',
            'role' => UserRole::Teacher->value,
            'password' => 'bezpecne123',
        ]);

        $teacher = User::where('email', 'peter@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('bezpecne123', $teacher->password));
        Notification::assertNothingSentTo($teacher);
    }

    public function test_school_admin_cannot_assign_school_or_privileged_roles(): void
    {
        $otherSchool = School::factory()->create();

        $this->actingAs($this->schoolAdmin)
            ->post(route('users.store'), [
                'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@example.com',
                'role' => UserRole::SchoolAdmin->value,
                'school_id' => $otherSchool->id,
            ])
            ->assertSessionHasErrors(['role', 'school_id']);

        $this->assertDatabaseMissing(User::class, ['email' => 'x@example.com']);
    }

    public function test_school_admin_cannot_touch_users_of_another_school(): void
    {
        $foreignStudent = User::factory()->student()->create();

        $this->actingAs($this->schoolAdmin)->get(route('users.edit', $foreignStudent))->assertForbidden();
        $this->actingAs($this->schoolAdmin)
            ->put(route('users.update', $foreignStudent), [
                'first_name' => 'Hacked', 'last_name' => 'X', 'email' => $foreignStudent->email, 'role' => 'student',
            ])
            ->assertForbidden();
        $this->actingAs($this->schoolAdmin)->delete(route('users.destroy', $foreignStudent))->assertForbidden();

        $this->assertNotSame('Hacked', $foreignStudent->fresh()->first_name);
    }

    public function test_school_admin_cannot_edit_other_school_admin_of_same_school(): void
    {
        $colleague = User::factory()->schoolAdmin()->for($this->schoolAdmin->school)->create();

        $this->actingAs($this->schoolAdmin)->get(route('users.edit', $colleague))->assertForbidden();
    }

    public function test_user_list_shows_only_own_school(): void
    {
        $own = User::factory()->student()->for($this->schoolAdmin->school)->create(['last_name' => 'Vlastný']);
        User::factory()->student()->create(['last_name' => 'Cudzí']);

        Livewire::actingAs($this->schoolAdmin)
            ->test(UserTable::class)
            ->assertSee($own->email)
            ->assertDontSee('Cudzí');
    }

    public function test_deactivation_of_foreign_user_through_livewire_is_refused(): void
    {
        $foreignStudent = User::factory()->student()->create();

        Livewire::actingAs($this->schoolAdmin)
            ->test(UserTable::class)
            ->call('toggleActive', $foreignStudent->id)
            ->assertNotFound();

        $this->assertTrue($foreignStudent->fresh()->is_active);
    }

    public function test_school_admin_can_deactivate_own_student(): void
    {
        $student = User::factory()->student()->for($this->schoolAdmin->school)->create();

        Livewire::actingAs($this->schoolAdmin)
            ->test(UserTable::class)
            ->call('toggleActive', $student->id);

        $this->assertFalse($student->fresh()->is_active);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::UserDeactivated->value, 'auditable_id' => $student->id]);
    }

    public function test_super_admin_role_change_is_audited_and_own_role_is_locked(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($superAdmin)
            ->put(route('users.update', $teacher), [
                'first_name' => $teacher->first_name,
                'last_name' => $teacher->last_name,
                'email' => $teacher->email,
                'role' => UserRole::SchoolAdmin->value,
                'school_id' => $teacher->school_id,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertSame(UserRole::SchoolAdmin, $teacher->fresh()->role);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::UserRoleChanged->value, 'auditable_id' => $teacher->id]);

        $this->actingAs($superAdmin)
            ->put(route('users.update', $superAdmin), [
                'first_name' => 'A', 'last_name' => 'B', 'email' => $superAdmin->email,
                'role' => UserRole::Student->value, 'school_id' => $teacher->school_id,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->delete(route('users.destroy', $superAdmin))->assertForbidden();

        $this->assertNotSoftDeleted($superAdmin);
    }

    public function test_teachers_and_students_cannot_open_user_management(): void
    {
        $this->actingAs(User::factory()->teacher()->create())->get(route('users.index'))->assertForbidden();
        $this->actingAs(User::factory()->student()->create())->get(route('users.index'))->assertForbidden();
    }

    public function test_invited_user_can_set_password_and_log_in(): void
    {
        $this->actingAs($this->schoolAdmin)->post(route('users.store'), [
            'first_name' => 'Nový', 'last_name' => 'Žiak', 'email' => 'novy@example.com', 'role' => 'student',
        ]);
        Auth::guard('web')->logout();

        $student = User::where('email', 'novy@example.com')->firstOrFail();
        $url = null;
        Notification::assertSentTo($student, AccountInvitation::class, function (AccountInvitation $notification) use ($student, &$url): bool {
            $url = $notification->toMail($student)->actionUrl;

            return true;
        });

        $this->get($url)->assertOk()->assertSee(__('Aktivácia účtu'));

        $token = str($url)->after('/invitation/')->before('?')->toString();

        Livewire::test(ResetPassword::class, ['token' => $token, 'invitation' => true])
            ->set('email', 'novy@example.com')
            ->set('password', 'mojeHeslo42')
            ->set('password_confirmation', 'mojeHeslo42')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('mojeHeslo42', $student->fresh()->password));
    }

    public function test_invitation_link_stays_valid_longer_than_password_reset(): void
    {
        $student = User::factory()->student()->for($this->schoolAdmin->school)->create();
        $token = Password::broker('invitations')->createToken($student);

        $this->travel(2)->days();

        Livewire::test(ResetPassword::class, ['token' => $token, 'invitation' => true])
            ->set('email', $student->email)
            ->set('password', 'mojeHeslo42')
            ->set('password_confirmation', 'mojeHeslo42')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('mojeHeslo42', $student->fresh()->password));
    }
}
