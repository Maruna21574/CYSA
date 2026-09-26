<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\ClassroomRole;
use App\Enums\QuizPurpose;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Analytics\AnalyticsFilter;
use App\Services\Analytics\QuizAnalytics;
use App\Services\Analytics\ResearchService;
use App\Services\Quiz\AttemptService;
use App\Support\CsvExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private User $teacher;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->published()->create();
        $this->teacher = $this->course->author;
        $this->classroom = Classroom::factory()->for($this->course->school)->create(['name' => '4.A']);
        $this->course->assignments()->create(['classroom_id' => $this->classroom->id]);
    }

    /**
     * @param  list<Question>  $questions
     */
    private function quiz(array $questions, QuizPurpose $purpose = QuizPurpose::Graded): Quiz
    {
        $quiz = Quiz::factory()->forCourse($this->course)->published()->create(['purpose' => $purpose, 'pass_percentage' => 50]);

        foreach ($questions as $position => $question) {
            $quiz->questions()->attach($question->id, ['position' => $position]);
        }

        return $quiz;
    }

    private function student(string $lastName = 'Kováč'): User
    {
        $student = User::factory()->student()->for($this->course->school)->create(['last_name' => $lastName]);
        $this->classroom->members()->attach($student->id, ['role' => ClassroomRole::Student->value]);

        return $student;
    }

    /**
     * @param  list<bool>  $correct  one flag per question, in quiz order
     */
    private function take(Quiz $quiz, User $student, array $correct): QuizAttempt
    {
        $service = app(AttemptService::class);
        $attempt = $service->start($quiz, $student);

        foreach ($quiz->questions as $index => $question) {
            $option = $question->options()->where('is_correct', $correct[$index])->first();
            $service->saveAnswer($attempt, $question->id, ['selected' => [$option->id]]);
        }

        return $service->submit($attempt);
    }

    public function test_summary_question_and_wrong_answer_statistics(): void
    {
        $questions = Question::factory()->count(2)->by($this->teacher)->create()->all();
        $quiz = $this->quiz($questions);

        $this->take($quiz, $this->student(), [true, true]);
        $this->take($quiz, $this->student(), [true, false]);
        $this->take($quiz, $this->student(), [false, false]);

        $analytics = app(QuizAnalytics::class);
        $filter = new AnalyticsFilter($this->teacher);

        $summary = $analytics->summary($filter);
        $this->assertSame(3, $summary['students']);
        $this->assertEqualsWithDelta(50.0, $summary['average'], 0.01);
        $this->assertEqualsWithDelta(66.7, $summary['pass_rate'], 0.1);

        $stats = $analytics->questionStats($filter, 'asc', null)->keyBy('question_id');
        $this->assertEqualsWithDelta(66.7, $stats[$questions[0]->id]->success, 0.1);
        $this->assertEqualsWithDelta(33.3, $stats[$questions[1]->id]->success, 0.1);

        $wrong = $analytics->commonWrongAnswers($filter);
        $this->assertSame(3, $wrong->sum('picks'));

        $students = $analytics->studentStats($filter);
        $this->assertTrue($students->first()->struggling);
    }

    public function test_analytics_never_include_other_teachers_quizzes(): void
    {
        $foreignCourse = Course::factory()->published()->create();
        $foreignQuiz = Quiz::factory()->forCourse($foreignCourse)->published()->create();
        $foreignQuestion = Question::factory()->by($foreignCourse->author)->create();
        $foreignQuiz->questions()->attach($foreignQuestion->id, ['position' => 0]);
        $foreignStudent = User::factory()->student()->for($foreignCourse->school)->create();
        $foreignCourse->assignments()->create(['user_id' => $foreignStudent->id]);
        app(AttemptService::class)->submit(app(AttemptService::class)->start($foreignQuiz, $foreignStudent));

        $summary = app(QuizAnalytics::class)->summary(new AnalyticsFilter($this->teacher));

        $this->assertSame(0, $summary['attempts']);
    }

    public function test_foreign_quiz_results_and_exports_are_forbidden(): void
    {
        $quiz = $this->quiz(Question::factory()->count(1)->by($this->teacher)->create()->all());
        $colleague = User::factory()->teacher()->for($this->course->school)->create();

        $this->actingAs($colleague)->get(route('teacher.quizzes.results', $quiz))->assertForbidden();
        $this->actingAs($colleague)->get(route('teacher.quizzes.results.export', $quiz))->assertForbidden();
        $this->actingAs($colleague)->get(route('teacher.research.export', $quiz))->assertForbidden();
    }

    public function test_results_export_is_csv_and_audited(): void
    {
        $quiz = $this->quiz(Question::factory()->count(1)->by($this->teacher)->create()->all());
        $this->take($quiz, $this->student('=HYPERLINK("http://evil")'), [true]);

        $response = $this->actingAs($this->teacher)->get(route('teacher.quizzes.results.export', $quiz));

        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::DataExported->value]);
    }

    public function test_csv_cells_are_protected_against_formula_injection(): void
    {
        $this->assertSame("'=1+1", CsvExport::cell('=1+1'));
        $this->assertSame("'@SUM(A1)", CsvExport::cell('@SUM(A1)'));
        $this->assertSame("'-2+3", CsvExport::cell('-2+3'));
        $this->assertSame('-2,5', CsvExport::cell(-2.5));
        $this->assertSame('Kováč', CsvExport::cell('Kováč'));
    }

    public function test_pre_post_comparison_uses_first_attempts_and_pseudonyms(): void
    {
        $questions = Question::factory()->count(2)->by($this->teacher)->create()->all();
        $pre = $this->quiz($questions, QuizPurpose::PreTest);
        $post = $this->quiz($questions, QuizPurpose::PostTest);
        $pre->forceFill(['paired_quiz_id' => $post->id])->save();
        $post->forceFill(['paired_quiz_id' => $pre->id])->save();

        $a = $this->student('Alfa');
        $b = $this->student('Beta');

        $this->take($pre, $a, [false, false]);  // 0 %
        $this->take($pre, $a, [true, true]);    // second attempt must be ignored
        $this->take($post, $a, [true, true]);   // 100 %
        $this->take($pre, $b, [true, false]);   // 50 %
        $this->take($post, $b, [true, true]);   // 100 %

        $result = app(ResearchService::class)->comparison($pre, $post);

        $this->assertSame(2, $result['stats']['paired']);
        $this->assertEqualsWithDelta(25.0, $result['stats']['mean_pre'], 0.01);
        $this->assertEqualsWithDelta(100.0, $result['stats']['mean_post'], 0.01);
        $this->assertEqualsWithDelta(75.0, $result['stats']['mean_delta'], 0.01);
        $this->assertSame(2, $result['stats']['improved']);
        $this->assertNotNull($result['stats']['t']);
        $this->assertSame('4.A', $result['groups']->first()->group);

        $csv = $this->actingAs($this->teacher)->get(route('teacher.research.export', $pre))->assertOk()->streamedContent();

        $this->assertStringContainsString($a->research_code, $csv);
        $this->assertStringNotContainsString('Alfa', $csv);
        $this->assertStringNotContainsString($a->email, $csv);
        // header + (1 pre + 1 post attempt) * 2 questions * 2 students
        $this->assertSame(1 + 8, count(array_filter(explode("\n", trim($csv)))));
    }

    public function test_teacher_can_override_points_and_it_is_audited(): void
    {
        $quiz = $this->quiz(Question::factory()->count(2)->by($this->teacher)->create()->all());
        $attempt = $this->take($quiz, $this->student(), [true, false]);
        $answer = $attempt->answers()->where('is_correct', false)->first();

        $this->actingAs($this->teacher)
            ->patch(route('teacher.attempts.answers.score', [$attempt, $answer]), ['points' => 1])
            ->assertSessionHas('success');

        $attempt->refresh();
        $this->assertEquals(100, $attempt->percentage);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::AttemptScoreChanged->value, 'auditable_id' => $attempt->id]);

        $this->actingAs($this->teacher)
            ->patch(route('teacher.attempts.answers.score', [$attempt, $answer]), ['points' => 5])
            ->assertSessionHasErrors('points');
    }

    public function test_student_cannot_override_points(): void
    {
        $quiz = $this->quiz(Question::factory()->count(1)->by($this->teacher)->create()->all());
        $student = $this->student();
        $attempt = $this->take($quiz, $student, [false]);

        $this->actingAs($student)
            ->patch(route('teacher.attempts.answers.score', [$attempt, $attempt->answers()->first()]), ['points' => 1])
            ->assertForbidden();
    }

    public function test_analytics_pages_and_dashboards_render(): void
    {
        $questions = Question::factory()->count(1)->by($this->teacher)->create()->all();
        $pre = $this->quiz($questions, QuizPurpose::PreTest);
        $post = $this->quiz($questions, QuizPurpose::PostTest);
        $pre->forceFill(['paired_quiz_id' => $post->id])->save();
        $student = $this->student();
        $attempt = $this->take($pre, $student, [true]);
        $this->take($post, $student, [true]);

        foreach ([route('teacher.dashboard'), route('teacher.analytics.index'), route('teacher.research.index'), route('teacher.quizzes.results', $pre), route('attempts.show', $attempt)] as $url) {
            $this->actingAs($this->teacher)->get($url)->assertOk();
        }

        $this->actingAs($student)->get(route('student.dashboard'))->assertOk()->assertSee($this->course->title);
        $this->actingAs(User::factory()->schoolAdmin()->for($this->course->school)->create())->get(route('school.dashboard'))->assertOk();
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.dashboard'))->assertOk();
    }
}
