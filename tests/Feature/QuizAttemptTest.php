<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Enums\ClassroomRole;
use App\Enums\ResultVisibility;
use App\Events\QuizAttemptSubmitted;
use App\Livewire\Student\QuizPlayer;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Quiz\AttemptService;
use App\Services\Quiz\QuizUnavailableException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class QuizAttemptTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Quiz $quiz;

    private User $student;

    /** @var list<Question> */
    private array $questions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->published()->create();
        $this->quiz = Quiz::factory()->forCourse($this->course)->published()->create(['pass_percentage' => 50]);
        $this->questions = Question::factory()->count(2)->by($this->course->author)->create()->all();

        foreach ($this->questions as $position => $question) {
            $this->quiz->questions()->attach($question->id, ['position' => $position]);
        }

        $classroom = Classroom::factory()->for($this->course->school)->create();
        $this->student = User::factory()->student()->for($this->course->school)->create();
        $classroom->members()->attach($this->student->id, ['role' => ClassroomRole::Student->value]);
        $this->course->assignments()->create(['classroom_id' => $classroom->id]);
    }

    private function correctOptionId(Question $question): int
    {
        return $question->options()->where('is_correct', true)->value('id');
    }

    private function wrongOptionId(Question $question): int
    {
        return $question->options()->where('is_correct', false)->value('id');
    }

    public function test_student_takes_quiz_and_gets_graded(): void
    {
        Event::fake([QuizAttemptSubmitted::class]);

        $this->actingAs($this->student)
            ->post(route('student.quizzes.start', $this->quiz))
            ->assertRedirect();

        $attempt = QuizAttempt::firstOrFail();
        $this->assertSame(2, $attempt->answers()->count());

        [$first, $second] = $this->questions;

        Livewire::actingAs($this->student)
            ->test(QuizPlayer::class, ['attempt' => $attempt])
            ->set("responses.{$first->id}.selected.0", (string) $this->correctOptionId($first))
            ->set("responses.{$second->id}.selected.0", (string) $this->wrongOptionId($second))
            ->call('submit')
            ->assertRedirect(route('attempts.show', $attempt));

        $attempt->refresh();

        $this->assertSame(AttemptStatus::Completed, $attempt->status);
        $this->assertEquals(1, $attempt->score);
        $this->assertEquals(2, $attempt->max_score);
        $this->assertEquals(50, $attempt->percentage);
        $this->assertTrue($attempt->passed);
        $this->assertNotNull($attempt->time_spent_seconds);
        $this->assertSame(2, DB::table('quiz_answer_option')->count());
        Event::assertDispatched(QuizAttemptSubmitted::class, 1);
    }

    public function test_second_start_resumes_the_attempt_in_progress(): void
    {
        $service = app(AttemptService::class);

        $first = $service->start($this->quiz, $this->student);
        $second = $service->start($this->quiz, $this->student);

        $this->assertTrue($first->is($second));
        $this->assertSame(1, QuizAttempt::count());
    }

    public function test_quiz_page_does_not_leak_correct_answers(): void
    {
        $attempt = app(AttemptService::class)->start($this->quiz, $this->student);

        $html = Livewire::actingAs($this->student)->test(QuizPlayer::class, ['attempt' => $attempt])->html();

        $this->assertStringNotContainsString('is_correct', $html);
        $this->assertStringNotContainsString('question_snapshot', $html);
        $this->assertStringContainsString('Nesprávna A', $html);
    }

    public function test_submitted_attempt_cannot_be_changed(): void
    {
        $service = app(AttemptService::class);
        $attempt = $service->start($this->quiz, $this->student);
        [$first] = $this->questions;

        $service->saveAnswer($attempt, $first->id, ['selected' => [$this->wrongOptionId($first)]]);
        $service->submit($attempt);
        $score = $attempt->fresh()->score;

        Livewire::actingAs($this->student)
            ->test(QuizPlayer::class, ['attempt' => $attempt])
            ->assertRedirect(route('attempts.show', $attempt));

        $this->expectException(QuizUnavailableException::class);
        $service->saveAnswer($attempt, $first->id, ['selected' => [$this->correctOptionId($first)]]);

        $this->assertEquals($score, $attempt->fresh()->score);
    }

    public function test_double_submit_grades_only_once(): void
    {
        Event::fake([QuizAttemptSubmitted::class]);
        $service = app(AttemptService::class);
        $attempt = $service->start($this->quiz, $this->student);

        $service->submit($attempt);
        $service->submit($attempt);

        Event::assertDispatched(QuizAttemptSubmitted::class, 1);
    }

    public function test_grading_uses_the_snapshot_not_later_edits(): void
    {
        $service = app(AttemptService::class);
        $attempt = $service->start($this->quiz, $this->student);
        [$first] = $this->questions;
        $originallyCorrect = $this->correctOptionId($first);

        // Teacher changes the correct answer while the student is writing the test.
        $first->options()->update(['is_correct' => false]);
        $first->options()->whereKeyNot($originallyCorrect)->limit(1)->update(['is_correct' => true]);

        $service->saveAnswer($attempt, $first->id, ['selected' => [$originallyCorrect]]);
        $service->submit($attempt);

        $this->assertTrue($attempt->answers()->where('question_id', $first->id)->first()->is_correct);
    }

    public function test_time_limit_is_enforced_by_the_server(): void
    {
        $this->quiz->update(['time_limit_minutes' => 10]);
        $service = app(AttemptService::class);
        $attempt = $service->start($this->quiz, $this->student);
        [$first] = $this->questions;

        $this->travel(11)->minutes();

        try {
            $service->saveAnswer($attempt, $first->id, ['selected' => [$this->correctOptionId($first)]]);
            $this->fail('Answer after the time limit must be rejected.');
        } catch (QuizUnavailableException) {
        }

        $this->assertSame(1, $service->expireOverdue());

        $attempt->refresh();
        $this->assertSame(AttemptStatus::Completed, $attempt->status);
        $this->assertTrue($attempt->timed_out);
        $this->assertEquals(0, $attempt->score);
        $this->assertSame($attempt->expires_at->timestamp, $attempt->finished_at->timestamp);
    }

    public function test_attempt_limit_and_dates_are_respected(): void
    {
        $service = app(AttemptService::class);
        $this->quiz->update(['max_attempts' => 1]);

        $service->submit($service->start($this->quiz, $this->student));

        $this->actingAs($this->student)
            ->post(route('student.quizzes.start', $this->quiz))
            ->assertSessionHas('error');
        $this->assertSame(1, QuizAttempt::count());

        $this->quiz->update(['max_attempts' => null, 'due_at' => now()->subDay()]);
        $this->assertFalse($service->availability($this->quiz, $this->student)->canStart);

        $this->quiz->update(['due_at' => null, 'available_from' => now()->addDay()]);
        $this->assertFalse($service->availability($this->quiz, $this->student)->canStart);
    }

    public function test_unassigned_student_and_draft_quiz_are_refused(): void
    {
        $stranger = User::factory()->student()->for($this->course->school)->create();

        $this->actingAs($stranger)->get(route('student.quizzes.show', $this->quiz))->assertForbidden();
        $this->actingAs($stranger)->post(route('student.quizzes.start', $this->quiz))->assertForbidden();

        $this->quiz->forceFill(['status' => 'draft'])->save();
        $this->actingAs($this->student)->post(route('student.quizzes.start', $this->quiz))->assertForbidden();
    }

    public function test_other_student_cannot_see_or_take_attempt(): void
    {
        $attempt = app(AttemptService::class)->start($this->quiz, $this->student);
        $classmate = User::factory()->student()->for($this->course->school)->create();
        $this->course->assignments()->create(['user_id' => $classmate->id]);

        $this->actingAs($classmate)->get(route('attempts.show', $attempt))->assertForbidden();
        Livewire::actingAs($classmate)->test(QuizPlayer::class, ['attempt' => $attempt])->assertForbidden();
    }

    public function test_hidden_result_is_not_shown_to_student_but_to_teacher(): void
    {
        $this->quiz->update(['show_result' => ResultVisibility::Never, 'show_correct_answers' => ResultVisibility::Never]);
        $service = app(AttemptService::class);
        $attempt = $service->submit($service->start($this->quiz, $this->student));

        $this->actingAs($this->student)
            ->get(route('attempts.show', $attempt))
            ->assertOk()
            ->assertSee(__('Výsledok ti oznámi učiteľ.'))
            ->assertDontSee(__('Prehľad odpovedí'));

        $this->actingAs($this->course->author)
            ->get(route('attempts.show', $attempt))
            ->assertOk()
            ->assertSee(__('Prehľad odpovedí'));
    }

    public function test_student_pages_render(): void
    {
        $service = app(AttemptService::class);
        $attempt = $service->submit($service->start($this->quiz, $this->student));

        foreach ([
            route('courses.show', $this->course),
            route('student.quizzes.show', $this->quiz),
            route('attempts.show', $attempt),
            route('student.results.index'),
        ] as $url) {
            $this->actingAs($this->student)->get($url)->assertOk();
        }
    }
}
