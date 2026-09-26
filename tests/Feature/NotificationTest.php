<?php

namespace Tests\Feature;

use App\Enums\ClassroomRole;
use App\Enums\ResultVisibility;
use App\Livewire\NotificationBell;
use App\Livewire\Teacher\ChapterMaterials;
use App\Livewire\Teacher\CourseAssignments;
use App\Models\Chapter;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use App\Notifications\CourseAssignedNotification;
use App\Notifications\DeadlineApproachingNotification;
use App\Notifications\MaterialAddedNotification;
use App\Notifications\QuizPublishedNotification;
use App\Notifications\QuizResultNotification;
use App\Services\Quiz\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private User $teacher;

    private User $student;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->published()->create();
        $this->teacher = $this->course->author;
        Chapter::factory()->forCourse($this->course)->create();
        $this->classroom = Classroom::factory()->for($this->course->school)->create();
        $this->student = User::factory()->student()->for($this->course->school)->create();
        $this->classroom->members()->attach($this->student->id, ['role' => ClassroomRole::Student->value]);
    }

    private function assignClassroom(): void
    {
        $this->course->assignments()->create(['classroom_id' => $this->classroom->id]);
    }

    private function publishedQuiz(array $attributes = []): Quiz
    {
        $quiz = Quiz::factory()->forCourse($this->course)->published()->create($attributes);
        $question = Question::factory()->by($this->teacher)->create();
        $quiz->questions()->attach($question->id, ['position' => 0]);

        return $quiz;
    }

    public function test_students_are_notified_when_published_course_is_assigned(): void
    {
        Notification::fake();
        $inactive = User::factory()->student()->inactive()->for($this->course->school)->create();
        $this->classroom->members()->attach($inactive->id, ['role' => ClassroomRole::Student->value]);

        Livewire::actingAs($this->teacher)
            ->test(CourseAssignments::class, ['course' => $this->course])
            ->set('classroomId', (string) $this->classroom->id)
            ->call('assignClassroom');

        Notification::assertSentTo($this->student, CourseAssignedNotification::class);
        Notification::assertNotSentTo($inactive, CourseAssignedNotification::class);
        Notification::assertNotSentTo($this->teacher, CourseAssignedNotification::class);
    }

    public function test_nothing_is_sent_for_draft_course(): void
    {
        Notification::fake();
        $this->course->forceFill(['status' => 'draft'])->save();

        Livewire::actingAs($this->teacher)
            ->test(CourseAssignments::class, ['course' => $this->course])
            ->set('classroomId', (string) $this->classroom->id)
            ->call('assignClassroom');

        Notification::assertNothingSent();
    }

    public function test_quiz_publication_is_announced_only_once(): void
    {
        $this->assignClassroom();
        $quiz = Quiz::factory()->forCourse($this->course)->create();
        $quiz->questions()->attach(Question::factory()->by($this->teacher)->create()->id, ['position' => 0]);
        Notification::fake();

        $this->actingAs($this->teacher)->patch(route('teacher.quizzes.status', $quiz), ['status' => 'published']);
        $this->actingAs($this->teacher)->patch(route('teacher.quizzes.status', $quiz), ['status' => 'draft']);
        $this->actingAs($this->teacher)->patch(route('teacher.quizzes.status', $quiz), ['status' => 'published']);

        Notification::assertSentToTimes($this->student, QuizPublishedNotification::class, 1);
    }

    public function test_new_material_is_announced(): void
    {
        $this->assignClassroom();
        Notification::fake();

        Livewire::actingAs($this->teacher)
            ->test(ChapterMaterials::class, ['chapter' => $this->course->chapters()->first()])
            ->set('linkUrl', 'https://example.com/navod')
            ->call('saveLink');

        Notification::assertSentTo($this->student, MaterialAddedNotification::class);
    }

    public function test_result_notification_respects_quiz_visibility(): void
    {
        $this->assignClassroom();
        $service = app(AttemptService::class);

        $visible = $this->publishedQuiz();
        $hidden = $this->publishedQuiz(['show_result' => ResultVisibility::Never]);
        Notification::fake();

        $service->submit($service->start($visible, $this->student));
        $service->submit($service->start($hidden, $this->student));

        Notification::assertSentToTimes($this->student, QuizResultNotification::class, 1);
    }

    public function test_deadline_reminder_is_sent_once_and_not_to_finished_students(): void
    {
        $this->assignClassroom();
        $quiz = $this->publishedQuiz(['due_at' => now()->addHours(10)]);
        $finished = User::factory()->student()->for($this->course->school)->create();
        $this->classroom->members()->attach($finished->id, ['role' => ClassroomRole::Student->value]);
        $service = app(AttemptService::class);
        $service->submit($service->start($quiz, $finished));
        Notification::fake();

        $this->artisan('notifications:deadlines')->assertSuccessful();
        $this->artisan('notifications:deadlines')->assertSuccessful();

        Notification::assertSentToTimes($this->student, DeadlineApproachingNotification::class, 1);
        Notification::assertNotSentTo($finished, DeadlineApproachingNotification::class);
    }

    public function test_teacher_announcement_reaches_students(): void
    {
        $this->assignClassroom();
        Notification::fake();

        $this->actingAs($this->teacher)
            ->post(route('teacher.courses.announcements.store', $this->course), ['title' => 'Test v piatok', 'body' => 'Nezabudnite sa pripraviť.'])
            ->assertSessionHas('success');

        Notification::assertSentTo($this->student, AnnouncementNotification::class);

        $colleague = User::factory()->teacher()->for($this->course->school)->create();
        $this->actingAs($colleague)
            ->post(route('teacher.courses.announcements.store', $this->course), ['title' => 'x', 'body' => 'y'])
            ->assertForbidden();

        $this->actingAs($this->student)->get(route('courses.show', $this->course))->assertSee('Test v piatok');
    }

    public function test_email_channel_only_when_enabled(): void
    {
        $notification = new CourseAssignedNotification($this->course);

        $this->assertSame(['database'], $notification->via($this->student));

        $this->student->forceFill(['email_notifications' => true])->save();
        $this->assertSame(['database', 'mail'], $notification->via($this->student));
    }

    public function test_bell_marks_as_read_and_only_opens_own_notifications(): void
    {
        $this->student->notify(new CourseAssignedNotification($this->course));
        $notification = $this->student->notifications()->first();

        Livewire::actingAs($this->student)
            ->test(NotificationBell::class)
            ->assertSee(__('Nový kurz'))
            ->call('open', $notification->id)
            ->assertRedirect(route('courses.show', $this->course));

        $this->assertNotNull($notification->fresh()->read_at);

        Livewire::actingAs(User::factory()->student()->create())
            ->test(NotificationBell::class)
            ->call('open', $notification->id)
            ->assertNotFound();
    }

    public function test_notification_links_cannot_redirect_outside(): void
    {
        $this->assertSame(route('dashboard'), NotificationBell::safeUrl('https://evil.example/phish'));
        $this->assertSame(route('dashboard'), NotificationBell::safeUrl('//evil.example'));
        $this->assertSame('/courses/1', NotificationBell::safeUrl('/courses/1'));
    }

    public function test_profile_password_change_requires_current_password(): void
    {
        $this->actingAs($this->student)
            ->put(route('profile.password'), ['current_password' => 'wrong', 'password' => 'noveHeslo123', 'password_confirmation' => 'noveHeslo123'])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($this->student)
            ->put(route('profile.password'), ['current_password' => 'password', 'password' => 'noveHeslo123', 'password_confirmation' => 'noveHeslo123'])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('noveHeslo123', $this->student->fresh()->password));

        $this->actingAs($this->student)->put(route('profile.preferences'), ['email_notifications' => '1']);
        $this->assertTrue($this->student->fresh()->email_notifications);
    }

    public function test_pages_render(): void
    {
        $this->student->notify(new CourseAssignedNotification($this->course));

        $this->actingAs($this->student)->get(route('notifications.index'))->assertOk()->assertSee(__('Nový kurz'));
        $this->actingAs($this->student)->get(route('profile.edit'))->assertOk();
        $this->actingAs($this->teacher)->get(route('teacher.courses.announcements.index', $this->course))->assertOk();
    }
}
