<?php

namespace Tests\Feature;

use App\Events\CourseCompleted;
use App\Models\Chapter;
use App\Models\ChapterProgress;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private User $student;

    private Chapter $first;

    private Chapter $second;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->published()->create(['sequential_chapters' => true]);
        $this->first = Chapter::factory()->forCourse($this->course)->create(['position' => 0]);
        $this->second = Chapter::factory()->forCourse($this->course)->create(['position' => 1]);
        $this->student = User::factory()->student()->for($this->course->school)->create();
        $this->course->assignments()->create(['user_id' => $this->student->id]);
    }

    public function test_opening_a_chapter_marks_it_started(): void
    {
        $this->actingAs($this->student)->get(route('chapters.show', [$this->course, $this->first]))->assertOk();

        $this->assertDatabaseHas(ChapterProgress::class, ['user_id' => $this->student->id, 'chapter_id' => $this->first->id, 'completed_at' => null]);
        $this->assertDatabaseHas(CourseProgress::class, ['user_id' => $this->student->id, 'course_id' => $this->course->id, 'total_chapters' => 2]);
    }

    public function test_sequential_course_locks_next_chapter_until_previous_is_completed(): void
    {
        $this->actingAs($this->student)
            ->get(route('chapters.show', [$this->course, $this->second]))
            ->assertRedirect(route('courses.show', $this->course));

        $this->actingAs($this->student)
            ->post(route('student.chapters.complete', [$this->course, $this->second]))
            ->assertForbidden();

        $this->actingAs($this->student)
            ->post(route('student.chapters.complete', [$this->course, $this->first]))
            ->assertRedirect(route('chapters.show', [$this->course, $this->second]));

        $this->actingAs($this->student)->get(route('chapters.show', [$this->course, $this->second]))->assertOk();
    }

    public function test_completing_all_chapters_completes_the_course_once(): void
    {
        Event::fake([CourseCompleted::class]);

        $this->actingAs($this->student)->post(route('student.chapters.complete', [$this->course, $this->first]));
        $this->assertEquals(50, CourseProgress::first()->percentage);

        $this->actingAs($this->student)->post(route('student.chapters.complete', [$this->course, $this->second]));
        $this->actingAs($this->student)->post(route('student.chapters.complete', [$this->course, $this->second]));

        $progress = CourseProgress::first();
        $this->assertEquals(100, $progress->percentage);
        $this->assertNotNull($progress->completed_at);
        Event::assertDispatched(CourseCompleted::class, 1);
    }

    public function test_teacher_preview_is_never_locked_and_not_tracked(): void
    {
        $this->actingAs($this->course->author)->get(route('chapters.show', [$this->course, $this->second]))->assertOk();

        $this->assertSame(0, ChapterProgress::count());
    }
}
