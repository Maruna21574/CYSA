<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $schoolAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->schoolAdmin = User::factory()->schoolAdmin()->create();
    }

    public function test_students_are_imported_into_classroom_and_invited(): void
    {
        $classroom = Classroom::factory()->for($this->schoolAdmin->school)->create();
        $csv = "\xEF\xBB\xBFmeno;priezvisko;email\nJana;Kováčová;JANA@example.com\nMarek;Horváth;marek@example.com\n";

        $this->actingAs($this->schoolAdmin)
            ->post(route('school.students.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('ziaci.csv', $csv),
                'classroom_id' => $classroom->id,
                'send_invitation' => '1',
            ])
            ->assertRedirect(route('users.index', ['role' => 'student']))
            ->assertSessionHas('success');

        $jana = User::where('email', 'jana@example.com')->firstOrFail();

        $this->assertSame('Kováčová', $jana->last_name);
        $this->assertSame(UserRole::Student, $jana->role);
        $this->assertSame($this->schoolAdmin->school_id, $jana->school_id);
        $this->assertSame(2, $classroom->students()->count());
        Notification::assertSentTo($jana, AccountInvitation::class);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::UsersImported->value]);
    }

    public function test_nothing_is_imported_when_any_row_is_invalid(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $csv = "meno,priezvisko,email\nOk,Student,ok@example.com\nZly,,zly-email\nDuplicitny,Ucet,taken@example.com\n";

        $this->actingAs($this->schoolAdmin)
            ->post(route('school.students.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('ziaci.csv', $csv),
            ])
            ->assertSessionHas('import_errors', fn (array $errors): bool => array_keys($errors) === [3, 4]);

        $this->assertDatabaseMissing(User::class, ['email' => 'ok@example.com']);
        Notification::assertNothingSent();
    }

    public function test_file_without_required_columns_is_rejected(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->post(route('school.students.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('ziaci.csv', "name;mail\nJana;jana@example.com\n"),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_classroom_of_another_school_cannot_be_used(): void
    {
        $foreignClassroom = Classroom::factory()->create();

        $this->actingAs($this->schoolAdmin)
            ->post(route('school.students.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('ziaci.csv', "meno;priezvisko;email\nJana;K;j@example.com\n"),
                'classroom_id' => $foreignClassroom->id,
            ])
            ->assertSessionHasErrors('classroom_id');

        $this->assertDatabaseMissing(User::class, ['email' => 'j@example.com']);
    }

    public function test_non_csv_upload_is_rejected(): void
    {
        $this->actingAs($this->schoolAdmin)
            ->post(route('school.students.import.store'), [
                'file' => UploadedFile::fake()->create('virus.php', 10, 'application/x-php'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_teacher_cannot_import_students(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('school.students.import'))->assertForbidden();
    }
}
