<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\QuestionStatus;
use App\Enums\QuizPurpose;
use App\Enums\QuizStatus;
use App\Livewire\Teacher\QuizBuilder;
use App\Models\AuditLog;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
        $this->course = Course::factory()->by($this->teacher)->create();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'course_id' => $this->course->id,
            'title' => 'Test: phishing',
            'purpose' => 'graded',
            'pass_percentage' => 70,
            'max_attempts' => 2,
            'time_limit_minutes' => 20,
            'show_result' => 'immediately',
            'show_correct_answers' => 'immediately',
            'shuffle_questions' => '1',
            ...$overrides,
        ];
    }

    public function test_teacher_creates_quiz(): void
    {
        $this->actingAs($this->teacher)->post(route('teacher.quizzes.store'), $this->payload())->assertRedirect();

        $quiz = Quiz::firstOrFail();

        $this->assertSame($this->teacher->id, $quiz->author_id);
        $this->assertSame(QuizPurpose::Graded, $quiz->purpose);
        $this->assertSame(2, $quiz->max_attempts);
        $this->assertTrue($quiz->shuffle_questions);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::QuizCreated->value]);
    }

    public function test_quiz_cannot_use_foreign_course_or_chapter(): void
    {
        $foreignCourse = Course::factory()->create();
        $foreignChapter = Chapter::factory()->create();

        $this->actingAs($this->teacher)
            ->post(route('teacher.quizzes.store'), $this->payload(['course_id' => $foreignCourse->id]))
            ->assertSessionHasErrors('course_id');

        $this->actingAs($this->teacher)
            ->post(route('teacher.quizzes.store'), $this->payload(['chapter_id' => $foreignChapter->id]))
            ->assertSessionHasErrors('chapter_id');

        $this->assertSame(0, Quiz::count());
    }

    public function test_pre_and_post_test_are_paired_symmetrically(): void
    {
        $pre = Quiz::factory()->forCourse($this->course)->create(['purpose' => QuizPurpose::PreTest]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.quizzes.store'), $this->payload(['purpose' => 'posttest', 'paired_quiz_id' => $pre->id]))
            ->assertSessionHasNoErrors();

        $post = Quiz::where('purpose', 'posttest')->firstOrFail();

        $this->assertSame($pre->id, $post->paired_quiz_id);
        $this->assertSame($post->id, $pre->fresh()->paired_quiz_id);
    }

    public function test_pairing_requires_opposite_purpose(): void
    {
        $other = Quiz::factory()->forCourse($this->course)->create(['purpose' => QuizPurpose::PostTest]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.quizzes.store'), $this->payload(['purpose' => 'posttest', 'paired_quiz_id' => $other->id]))
            ->assertSessionHasErrors('paired_quiz_id');
    }

    public function test_due_date_must_be_after_available_from(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.quizzes.store'), $this->payload(['available_from' => '2026-10-10 10:00', 'due_at' => '2026-10-01 10:00']))
            ->assertSessionHasErrors('due_at');
    }

    public function test_publishing_requires_approved_questions(): void
    {
        $quiz = Quiz::factory()->forCourse($this->course)->create();

        $this->actingAs($this->teacher)->patch(route('teacher.quizzes.status', $quiz), ['status' => 'published'])->assertSessionHas('error');

        $draft = Question::factory()->by($this->teacher)->create();
        $draft->forceFill(['status' => QuestionStatus::Draft])->save();
        $quiz->questions()->attach($draft->id, ['position' => 0]);

        $this->actingAs($this->teacher)->patch(route('teacher.quizzes.status', $quiz), ['status' => 'published'])->assertSessionHas('error');
        $this->assertSame(QuizStatus::Draft, $quiz->fresh()->status);

        $draft->forceFill(['status' => QuestionStatus::Approved])->save();
        $this->actingAs($this->teacher)->patch(route('teacher.quizzes.status', $quiz), ['status' => 'published']);

        $this->assertSame(QuizStatus::Published, $quiz->fresh()->status);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::QuizPublished->value]);
    }

    public function test_builder_adds_orders_and_scores_questions(): void
    {
        $quiz = Quiz::factory()->forCourse($this->course)->create();
        [$a, $b, $c] = Question::factory()->count(3)->by($this->teacher)->create()->all();

        $component = Livewire::actingAs($this->teacher)
            ->test(QuizBuilder::class, ['quiz' => $quiz])
            ->call('add', $a->id)
            ->call('add', $b->id)
            ->call('add', $c->id)
            ->call('sortQuestion', $c->id, 0)
            ->call('setPoints', $b->id, '2.5');

        $this->assertSame([$c->id, $a->id, $b->id], $quiz->questions()->pluck('questions.id')->all());
        $this->assertEqualsWithDelta(4.5, $quiz->fresh()->load('questions')->totalPoints(), 0.001);

        $component->call('remove', $a->id);
        $this->assertSame(2, $quiz->questions()->count());
    }

    public function test_builder_refuses_questions_from_foreign_bank(): void
    {
        $quiz = Quiz::factory()->forCourse($this->course)->create();
        $foreign = Question::factory()->create();

        Livewire::actingAs($this->teacher)
            ->test(QuizBuilder::class, ['quiz' => $quiz])
            ->call('add', $foreign->id)
            ->assertNotFound();

        $this->assertSame(0, $quiz->questions()->count());
    }

    public function test_other_teacher_cannot_manage_quiz(): void
    {
        $quiz = Quiz::factory()->forCourse($this->course)->create();
        $colleague = User::factory()->teacher()->for($this->teacher->school)->create();

        $this->actingAs($colleague)->get(route('teacher.quizzes.show', $quiz))->assertForbidden();
        $this->actingAs($colleague)->patch(route('teacher.quizzes.status', $quiz), ['status' => 'published'])->assertForbidden();

        Livewire::actingAs($colleague)->test(QuizBuilder::class, ['quiz' => $quiz])->assertForbidden();
    }

    public function test_quiz_and_question_pages_render(): void
    {
        $quiz = Quiz::factory()->forCourse($this->course)->create();
        $question = Question::factory()->by($this->teacher)->create();
        $quiz->questions()->attach($question->id, ['position' => 0]);

        foreach ([
            route('teacher.quizzes.index'),
            route('teacher.quizzes.create'),
            route('teacher.quizzes.show', $quiz),
            route('teacher.quizzes.edit', $quiz),
            route('teacher.questions.index'),
            route('teacher.questions.create'),
            route('teacher.questions.edit', $question),
        ] as $url) {
            $this->actingAs($this->teacher)->get($url)->assertOk();
        }
    }
}
