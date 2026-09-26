<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\ClassroomRole;
use App\Livewire\Admin\AuditLogTable;
use App\Livewire\Auth\Login;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit viewer, system settings, search scoping, GDPR and security headers.
 */
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_sees_audit_log(): void
    {
        $admin = User::factory()->superAdmin()->create();
        AuditLog::create(['action' => AuditAction::LoginFailed->value, 'ip_address' => '10.1.2.3', 'metadata' => ['email' => 'x@example.com']]);

        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk();
        Livewire::actingAs($admin)->test(AuditLogTable::class)->assertSee('10.1.2.3');
        Livewire::actingAs($admin)->test(AuditLogTable::class)->set('search', '99.99')->assertDontSee('10.1.2.3');

        $this->actingAs(User::factory()->schoolAdmin()->create())->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    public function test_system_banner_is_shown_to_everyone(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'banner_enabled' => '1', 'banner_type' => 'warning', 'banner_message' => 'Sobotná údržba 8:00 – 10:00', 'default_gamification' => '1',
        ])->assertSessionHas('success');

        $this->assertTrue(SystemSetting::get('banner_enabled'));
        $this->actingAs(User::factory()->student()->create())->get(route('student.dashboard'))->assertSee('Sobotná údržba 8:00 – 10:00');
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::SystemSettingsChanged->value]);
    }

    public function test_search_respects_access_rights(): void
    {
        $course = Course::factory()->published()->create(['title' => 'Phishing pre začiatočníkov']);
        $hidden = Course::factory()->published()->create(['title' => 'Phishing pokročilý']);
        $student = User::factory()->student()->for($course->school)->create();
        $course->assignments()->create(['user_id' => $student->id]);

        $this->actingAs($student)->get(route('search', ['q' => 'phishing']))
            ->assertOk()
            ->assertSee('Phishing pre začiatočníkov')
            ->assertDontSee('Phishing pokročilý');

        $this->actingAs($course->author)->get(route('search', ['q' => 'phishing']))
            ->assertSee('Phishing pre začiatočníkov')
            ->assertDontSee('Phishing pokročilý');

        $foreignStudent = User::factory()->student()->create(['last_name' => 'Cudziaková']);
        $schoolAdmin = User::factory()->schoolAdmin()->for($course->school)->create();
        $this->actingAs($schoolAdmin)->get(route('search', ['q' => 'Cudziaková']))->assertDontSee(route('users.edit', $foreignStudent));
        $this->assertNotNull($hidden);
    }

    public function test_personal_data_export_contains_only_own_data(): void
    {
        $student = User::factory()->student()->create(['first_name' => 'Jana']);
        $classmate = User::factory()->student()->for($student->school)->create(['first_name' => 'Iný']);

        $response = $this->actingAs($student)->get(route('profile.export'));

        $response->assertOk()->assertHeader('Content-Disposition');
        $data = $response->json();
        $this->assertSame('Jana', $data['account']['first_name']);
        $this->assertStringNotContainsString($classmate->email, $response->getContent());
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::DataExported->value, 'user_id' => $student->id]);
    }

    public function test_anonymization_erases_personal_data(): void
    {
        $schoolAdmin = User::factory()->schoolAdmin()->create();
        $student = User::factory()->student()->for($schoolAdmin->school)->create(['email' => 'jana@example.com']);
        $classroom = Classroom::factory()->for($schoolAdmin->school)->create();
        $classroom->members()->attach($student->id, ['role' => ClassroomRole::Student->value]);
        $course = Course::factory()->create();
        $certificate = new Certificate;
        $certificate->forceFill(['code' => 'CYSA-AAAA-BBBB-CCCC', 'user_id' => $student->id, 'course_id' => $course->id, 'holder_name' => 'Jana Nová', 'course_title' => 'X', 'issued_at' => now()])->save();
        $researchCode = $student->research_code;

        $this->actingAs($schoolAdmin)->post(route('users.anonymize', $student), ['confirmation' => 'zle'])->assertSessionHasErrors('confirmation');
        $this->actingAs($schoolAdmin)->post(route('users.anonymize', $student), ['confirmation' => 'ANONYMIZOVAŤ'])->assertRedirect(route('users.index'));

        $student = User::withTrashed()->find($student->id);
        $this->assertSame("anonymized-{$student->id}@deleted.invalid", $student->email);
        $this->assertSame($researchCode, $student->research_code);
        $this->assertTrue($student->trashed());
        $this->assertSame(0, $classroom->members()->count());
        $this->assertNotNull($certificate->fresh()->revoked_at);
        $this->assertNotSame('Jana Nová', $certificate->fresh()->holder_name);

        Livewire::test(Login::class)->set('email', 'jana@example.com')->set('password', 'password')->call('login')->assertHasErrors('email');
    }

    public function test_content_security_policy_uses_nonce(): void
    {
        config(['cysa.csp' => true]);

        $response = $this->get(route('login'));

        $header = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($header);
        $this->assertStringContainsString("object-src 'none'", $header);
        preg_match("/'nonce-([^']+)'/", $header, $matches);
        $this->assertNotEmpty($matches[1]);
        $response->assertSee('nonce="'.$matches[1].'"', false);
    }

    public function test_error_pages_are_in_slovak(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get('/neexistujuca-stranka')
            ->assertNotFound()
            ->assertSee('Stránka neexistuje');
    }
}
