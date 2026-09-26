<?php

namespace Tests\Feature;

use App\Enums\ClassroomRole;
use App\Livewire\Teacher\CourseAssignments;
use App\Models\Chapter;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Who can see a course: assignment through a classroom or directly, publication, availability date.
 */
class CourseAccessTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Chapter $chapter;

    private User $teacher;

    private User $student;

    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->course = Course::factory()->published()->create();
        $this->teacher = $this->course->author;
        $this->chapter = Chapter::factory()->forCourse($this->course)->create(['title' => 'Bezpečné heslá']);
        $this->classroom = Classroom::factory()->for($this->course->school)->create();
        $this->student = User::factory()->student()->for($this->course->school)->create();
        $this->classroom->members()->attach($this->student->id, ['role' => ClassroomRole::Student->value]);
    }

    public function test_student_sees_course_assigned_to_their_classroom(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(CourseAssignments::class, ['course' => $this->course])
            ->set('classroomId', (string) $this->classroom->id)
            ->call('assignClassroom')
            ->assertHasNoErrors();

        $this->actingAs($this->student)->get(route('student.courses.index'))->assertSee($this->course->title);
        $this->actingAs($this->student)->get(route('courses.show', $this->course))->assertOk();
        $this->actingAs($this->student)->get(route('chapters.show', [$this->course, $this->chapter]))->assertOk()->assertSee('Bezpečné heslá');
    }

    public function test_unassigned_student_cannot_open_course(): void
    {
        $this->actingAs($this->student)->get(route('student.courses.index'))->assertDontSee($this->course->title);
        $this->actingAs($this->student)->get(route('courses.show', $this->course))->assertForbidden();
        $this->actingAs($this->student)->get(route('chapters.show', [$this->course, $this->chapter]))->assertForbidden();
    }

    public function test_draft_course_is_hidden_even_when_assigned(): void
    {
        $this->course->assignments()->create(['user_id' => $this->student->id]);
        $this->course->forceFill(['status' => 'draft'])->save();

        $this->actingAs($this->student)->get(route('courses.show', $this->course))->assertForbidden();
    }

    public function test_course_is_hidden_until_available_from(): void
    {
        $this->course->assignments()->create(['user_id' => $this->student->id, 'available_from' => now()->addDay()]);

        $this->actingAs($this->student)->get(route('courses.show', $this->course))->assertForbidden();

        $this->travel(2)->days();

        $this->actingAs($this->student)->get(route('courses.show', $this->course))->assertOk();
    }

    public function test_hidden_chapter_is_not_visible_to_student(): void
    {
        $this->course->assignments()->create(['user_id' => $this->student->id]);
        $hidden = Chapter::factory()->forCourse($this->course)->hidden()->create();

        $this->actingAs($this->student)->get(route('chapters.show', [$this->course, $hidden]))->assertForbidden();
        $this->actingAs($this->teacher)->get(route('chapters.show', [$this->course, $hidden]))->assertOk();
    }

    public function test_classroom_of_another_school_cannot_be_assigned(): void
    {
        $foreignClassroom = Classroom::factory()->create();

        Livewire::actingAs($this->teacher)
            ->test(CourseAssignments::class, ['course' => $this->course])
            ->set('classroomId', (string) $foreignClassroom->id)
            ->call('assignClassroom')
            ->assertNotFound();

        $this->assertSame(0, $this->course->assignments()->count());
    }

    public function test_student_of_another_school_cannot_be_assigned(): void
    {
        $foreignStudent = User::factory()->student()->create();

        Livewire::actingAs($this->teacher)
            ->test(CourseAssignments::class, ['course' => $this->course])
            ->call('assignStudent', $foreignStudent->id)
            ->assertNotFound();
    }

    public function test_chapter_of_another_course_is_not_reachable_through_this_course(): void
    {
        $this->course->assignments()->create(['user_id' => $this->student->id]);
        $foreignChapter = Chapter::factory()->create();

        $this->actingAs($this->student)->get(route('chapters.show', [$this->course, $foreignChapter]))->assertNotFound();
    }
}
