<?php

namespace Tests\Feature;

use App\Gamification\GamificationService;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserStat;
use App\Models\XpTransaction;
use App\Notifications\BadgeEarnedNotification;
use App\Services\Progress\ProgressService;
use App\Services\Quiz\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GamificationTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Quiz $quiz;

    private Question $question;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->published()->create();
        $this->quiz = Quiz::factory()->forCourse($this->course)->published()->create(['pass_percentage' => 50]);
        $this->question = Question::factory()->by($this->course->author)->create();
        $this->quiz->questions()->attach($this->question->id, ['position' => 0]);
        $this->student = User::factory()->student()->for($this->course->school)->create();
        $this->course->assignments()->create(['user_id' => $this->student->id]);
    }

    private function takeQuiz(bool $correct): void
    {
        $service = app(AttemptService::class);
        $attempt = $service->start($this->quiz, $this->student);
        $service->saveAnswer($attempt, $this->question->id, ['selected' => [$this->question->options()->where('is_correct', $correct)->value('id')]]);
        $service->submit($attempt);
    }

    private function hasBadge(string $key): bool
    {
        return DB::table('user_badges')
            ->join('badges', 'badges.id', '=', 'user_badges.badge_id')
            ->where('user_badges.user_id', $this->student->id)
            ->where('badges.key', $key)
            ->exists();
    }

    public function test_levels(): void
    {
        $this->assertSame(1, GamificationService::levelFor(0));
        $this->assertSame(1, GamificationService::levelFor(49));
        $this->assertSame(2, GamificationService::levelFor(50));
        $this->assertSame(3, GamificationService::levelFor(200));
        $this->assertSame(450, GamificationService::xpForLevel(4));
    }

    public function test_passing_a_quiz_awards_xp_once_and_badges(): void
    {
        Notification::fake();

        $this->takeQuiz(true);
        $this->takeQuiz(true);

        $quizXp = XpTransaction::where('user_id', $this->student->id)->where('reason', 'quiz_passed')->get();
        $this->assertCount(1, $quizXp);
        $this->assertSame(GamificationService::XP_QUIZ_PASSED + 10, $quizXp->first()->amount);

        $this->assertTrue($this->hasBadge('first_pass'));
        $this->assertTrue($this->hasBadge('perfect_score'));
        Notification::assertSentTo($this->student, BadgeEarnedNotification::class);

        $stats = UserStat::find($this->student->id);
        $this->assertSame((int) XpTransaction::where('user_id', $this->student->id)->sum('amount'), $stats->xp_total);
        $this->assertSame(GamificationService::levelFor($stats->xp_total), $stats->level);
    }

    public function test_failed_quiz_gives_no_xp_or_badge(): void
    {
        $this->takeQuiz(false);

        $this->assertSame(0, XpTransaction::where('reason', 'quiz_passed')->count());
        $this->assertFalse($this->hasBadge('first_pass'));
        $this->assertSame(1, UserStat::find($this->student->id)->current_streak);
    }

    public function test_chapter_xp_is_idempotent(): void
    {
        $chapter = Chapter::factory()->forCourse($this->course)->create();
        $progress = app(ProgressService::class);

        $progress->markCompleted($this->student, $chapter);
        app(GamificationService::class)->award($this->student, GamificationService::XP_CHAPTER, 'chapter_completed', $chapter);

        $this->assertSame(1, XpTransaction::where('reason', 'chapter_completed')->count());
    }

    public function test_streak_counts_consecutive_days_and_resets(): void
    {
        $gamification = app(GamificationService::class);

        foreach (range(1, 7) as $day) {
            $gamification->recordActivity($this->student);
            $this->travel(1)->days();
        }

        $this->assertSame(7, UserStat::find($this->student->id)->current_streak);
        $gamification->checkBadges($this->student);
        $this->assertTrue($this->hasBadge('streak_7'));

        $this->travel(3)->days();
        $gamification->recordActivity($this->student);

        $stats = UserStat::find($this->student->id);
        $this->assertSame(1, $stats->current_streak);
        $this->assertSame(7, $stats->longest_streak);
    }

    public function test_disabled_school_gets_no_gamification(): void
    {
        $school = $this->student->school;
        $school->settings = ['gamification' => false];
        $school->save();

        $this->takeQuiz(true);

        $this->assertSame(0, XpTransaction::count());
        $this->actingAs($this->student->fresh())->get(route('student.achievements'))->assertNotFound();
    }

    public function test_teachers_do_not_collect_xp(): void
    {
        $this->assertFalse(app(GamificationService::class)->enabledFor($this->course->author));
    }

    public function test_school_admin_toggles_gamification_and_pages_render(): void
    {
        $admin = User::factory()->schoolAdmin()->for($this->student->school)->create();

        $this->actingAs($admin)->get(route('school.settings.edit'))->assertOk();
        $this->actingAs($admin)->put(route('school.settings.update'), ['gamification' => '0'])->assertSessionHas('success');
        $this->assertFalse($this->student->school->fresh()->gamificationEnabled());

        $this->actingAs($admin)->put(route('school.settings.update'), ['gamification' => '1']);
        $this->takeQuiz(true);

        $this->actingAs($this->student)->get(route('student.achievements'))->assertOk()->assertSee('Prvý úspešný test');
        $this->actingAs($this->student)->get(route('student.dashboard'))->assertOk()->assertSee('XP');
    }
}
