<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\QuizPurpose;
use App\Events\CertificateIssued;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Progress\ProgressService;
use App\Services\Quiz\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Chapter $chapter;

    private Quiz $quiz;

    private Question $question;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->published()->create(['title' => 'Kybernetická bezpečnosť']);
        $this->course->forceFill(['certificate_enabled' => true, 'certificate_min_percentage' => 70])->save();
        $this->chapter = Chapter::factory()->forCourse($this->course)->create();
        $this->quiz = Quiz::factory()->forCourse($this->course)->published()->create(['purpose' => QuizPurpose::Graded, 'pass_percentage' => 60]);
        $this->question = Question::factory()->by($this->course->author)->create();
        $this->quiz->questions()->attach($this->question->id, ['position' => 0]);
        $this->student = User::factory()->student()->for($this->course->school)->create(['first_name' => 'Jana', 'last_name' => 'Kováčová']);
        $this->course->assignments()->create(['user_id' => $this->student->id]);
    }

    private function passQuiz(bool $correct = true): void
    {
        $service = app(AttemptService::class);
        $attempt = $service->start($this->quiz, $this->student);
        $option = $this->question->options()->where('is_correct', $correct)->first();
        $service->saveAnswer($attempt, $this->question->id, ['selected' => [$option->id]]);
        $service->submit($attempt);
    }

    public function test_certificate_is_issued_automatically_when_conditions_are_met(): void
    {
        Event::fake([CertificateIssued::class]);

        app(ProgressService::class)->markCompleted($this->student, $this->chapter);
        $this->assertSame(0, Certificate::count());

        $this->passQuiz();

        $certificate = Certificate::firstOrFail();
        $this->assertSame('Jana Kováčová', $certificate->holder_name);
        $this->assertSame('Kybernetická bezpečnosť', $certificate->course_title);
        $this->assertSame($this->course->school->name, $certificate->school_name);
        $this->assertMatchesRegularExpression('/^CYSA-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $certificate->code);
        $this->assertEquals(100, $certificate->final_percentage);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::CertificateIssued->value]);
        Event::assertDispatched(CertificateIssued::class, 1);

        // Further activity never creates a second certificate.
        $this->passQuiz();
        $this->assertSame(1, Certificate::count());
    }

    public function test_no_certificate_when_quiz_failed(): void
    {
        app(ProgressService::class)->markCompleted($this->student, $this->chapter);
        $this->passQuiz(correct: false);

        $this->assertSame(0, Certificate::count());
    }

    public function test_holder_name_snapshot_does_not_change(): void
    {
        app(ProgressService::class)->markCompleted($this->student, $this->chapter);
        $this->passQuiz();

        $this->student->update(['last_name' => 'Nová']);

        $this->assertSame('Jana Kováčová', Certificate::first()->holder_name);
    }

    public function test_pdf_download_only_for_holder_and_course_managers(): void
    {
        app(ProgressService::class)->markCompleted($this->student, $this->chapter);
        $this->passQuiz();
        $certificate = Certificate::firstOrFail();

        $this->actingAs($this->student)
            ->get(route('certificates.download', $certificate))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($this->course->author)->get(route('certificates.download', $certificate))->assertOk();

        $classmate = User::factory()->student()->for($this->course->school)->create();
        $this->actingAs($classmate)->get(route('certificates.download', $certificate))->assertForbidden();
    }

    public function test_public_verification_shows_minimal_data(): void
    {
        app(ProgressService::class)->markCompleted($this->student, $this->chapter);
        $this->passQuiz();
        $certificate = Certificate::firstOrFail();

        $this->get(route('certificates.verify', strtolower($certificate->code)))
            ->assertOk()
            ->assertSee(__('Certifikát je platný'))
            ->assertSee('Jana Kováčová')
            ->assertSee('Kybernetická bezpečnosť')
            ->assertDontSee($this->student->email);

        $this->get(route('certificates.verify', 'CYSA-AAAA-BBBB-CCCC'))->assertOk()->assertSee(__('Certifikát s týmto kódom neexistuje.'));
        $this->get(route('certificates.verify').'?code='.urlencode(' cysa-aaaa-bbbb-cccc '))->assertRedirect(route('certificates.verify', 'CYSA-AAAA-BBBB-CCCC'));
    }

    public function test_teacher_can_revoke_certificate(): void
    {
        app(ProgressService::class)->markCompleted($this->student, $this->chapter);
        $this->passQuiz();
        $certificate = Certificate::firstOrFail();

        $this->actingAs($this->student)->post(route('teacher.certificates.revoke', $certificate), ['reason' => 'x'])->assertForbidden();

        $this->actingAs($this->course->author)
            ->post(route('teacher.certificates.revoke', $certificate), ['reason' => 'Podvod pri teste'])
            ->assertSessionHas('success');

        $this->get(route('certificates.verify', $certificate->code))->assertSee(__('Certifikát bol zrušený a nie je platný.'));
        $this->actingAs($this->student)->get(route('certificates.download', $certificate))->assertStatus(410);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::CertificateRevoked->value]);
    }

    public function test_explicit_requirements_and_foreign_ids(): void
    {
        $extra = Chapter::factory()->forCourse($this->course)->create();
        $foreignChapter = Chapter::factory()->create();

        $this->actingAs($this->course->author)
            ->put(route('teacher.courses.certificate.update', $this->course), [
                'certificate_enabled' => '1',
                'certificate_min_percentage' => 50,
                'chapters' => [$foreignChapter->id],
            ])
            ->assertSessionHasErrors('chapters.0');

        $this->actingAs($this->course->author)
            ->put(route('teacher.courses.certificate.update', $this->course), [
                'certificate_enabled' => '1',
                'certificate_min_percentage' => 50,
                'chapters' => [$this->chapter->id],
                'quizzes' => [$this->quiz->id],
            ])
            ->assertSessionHasNoErrors();

        // Only the required chapter is needed - the extra one may stay unfinished.
        app(ProgressService::class)->markCompleted($this->student, $this->chapter);
        $this->passQuiz();

        $this->assertSame(1, Certificate::count());
        $this->assertNotNull($extra);
    }

    public function test_certificate_pages_render(): void
    {
        $this->actingAs($this->student)->get(route('student.certificates.index'))->assertOk()->assertSee(__('Čo ti chýba k certifikátu'));
        $this->actingAs($this->course->author)->get(route('teacher.courses.certificate.edit', $this->course))->assertOk();
        $this->get(route('certificates.verify'))->assertOk();
    }
}
