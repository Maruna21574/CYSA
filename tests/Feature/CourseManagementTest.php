<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\CourseStatus;
use App\Livewire\Teacher\CourseBuilder;
use App\Models\AuditLog;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
    }

    public function test_teacher_can_create_course_with_default_module(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.courses.store'), [
                'title' => 'Základy kybernetickej bezpečnosti',
                'difficulty' => 'beginner',
                'description' => 'Úvodný kurz',
            ])
            ->assertRedirect();

        $course = Course::firstOrFail();

        $this->assertSame($this->teacher->id, $course->author_id);
        $this->assertSame($this->teacher->school_id, $course->school_id);
        $this->assertSame('zaklady-kybernetickej-bezpecnosti', $course->slug);
        $this->assertSame(CourseStatus::Draft, $course->status);
        $this->assertSame(1, $course->modules()->count());
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::CourseCreated->value]);
    }

    public function test_students_cannot_create_courses(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->post(route('teacher.courses.store'), ['title' => 'X', 'difficulty' => 'beginner'])
            ->assertForbidden();
    }

    public function test_teacher_cannot_manage_course_of_colleague_but_school_admin_can(): void
    {
        $course = Course::factory()->create();
        $colleague = User::factory()->teacher()->for($course->school)->create();
        $schoolAdmin = User::factory()->schoolAdmin()->for($course->school)->create();

        $this->actingAs($colleague)->get(route('teacher.courses.show', $course))->assertForbidden();
        $this->actingAs($colleague)->put(route('teacher.courses.update', $course), ['title' => 'X', 'difficulty' => 'beginner'])->assertForbidden();
        $this->actingAs($schoolAdmin)->get(route('teacher.courses.show', $course))->assertOk();
    }

    public function test_school_admin_cannot_manage_course_of_another_school(): void
    {
        $course = Course::factory()->create();
        $foreignAdmin = User::factory()->schoolAdmin()->create();

        $this->actingAs($foreignAdmin)->get(route('teacher.courses.edit', $course))->assertForbidden();
        $this->actingAs($foreignAdmin)->delete(route('teacher.courses.destroy', $course))->assertForbidden();
    }

    public function test_course_list_shows_only_own_courses(): void
    {
        Course::factory()->by($this->teacher)->create(['title' => 'Môj kurz']);
        Course::factory()->create(['title' => 'Cudzí kurz']);

        $this->actingAs($this->teacher)
            ->get(route('teacher.courses.index'))
            ->assertOk()
            ->assertSee('Môj kurz')
            ->assertDontSee('Cudzí kurz');
    }

    public function test_course_without_published_chapter_cannot_be_published(): void
    {
        $course = Course::factory()->by($this->teacher)->create();

        $this->actingAs($this->teacher)
            ->patch(route('teacher.courses.status', $course), ['status' => 'published'])
            ->assertSessionHas('error');

        $this->assertSame(CourseStatus::Draft, $course->fresh()->status);
    }

    public function test_course_can_be_published_and_archived(): void
    {
        $course = Course::factory()->by($this->teacher)->create();
        Chapter::factory()->forCourse($course)->create();

        $this->actingAs($this->teacher)->patch(route('teacher.courses.status', $course), ['status' => 'published']);
        $this->assertSame(CourseStatus::Published, $course->fresh()->status);
        $this->assertNotNull($course->fresh()->published_at);

        $this->actingAs($this->teacher)->patch(route('teacher.courses.status', $course), ['status' => 'archived']);
        $this->assertSame(CourseStatus::Archived, $course->fresh()->status);

        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::CoursePublished->value]);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::CourseArchived->value]);
    }

    public function test_builder_adds_modules_and_moves_chapters_between_modules(): void
    {
        $course = Course::factory()->by($this->teacher)->create();
        $first = $course->modules()->create(['title' => 'Prvý', 'position' => 0]);
        $a = Chapter::factory()->forCourse($course)->create(['position' => 0]);
        $b = Chapter::factory()->forCourse($course)->create(['position' => 1]);

        $component = Livewire::actingAs($this->teacher)
            ->test(CourseBuilder::class, ['course' => $course])
            ->set('newModuleTitle', 'Druhý')
            ->call('addModule')
            ->assertHasNoErrors();

        $second = $course->modules()->where('title', 'Druhý')->firstOrFail();

        $component->call('sortChapter', $b->id, 0, $first->id);
        $this->assertSame([$b->id, $a->id], $first->chapters()->pluck('id')->all());

        $component->call('sortChapter', $a->id, 0, $second->id);
        $this->assertSame($second->id, $a->fresh()->module_id);
    }

    public function test_builder_refuses_chapters_and_modules_of_another_course(): void
    {
        $course = Course::factory()->by($this->teacher)->create();
        $foreignChapter = Chapter::factory()->create();

        Livewire::actingAs($this->teacher)
            ->test(CourseBuilder::class, ['course' => $course])
            ->call('deleteChapter', $foreignChapter->id)
            ->assertNotFound();

        $this->assertNotSoftDeleted($foreignChapter);
    }

    public function test_chapter_content_is_sanitized(): void
    {
        $course = Course::factory()->by($this->teacher)->create();
        $module = $course->modules()->create(['title' => 'M', 'position' => 0]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.courses.chapters.store', $course), [
                'title' => 'Phishing',
                'module_id' => $module->id,
                'content' => '<p onclick="steal()">Text<script>alert(1)</script></p><a href="javascript:alert(1)">x</a><a href="https://example.com">ok</a>',
                'is_published' => '1',
            ])
            ->assertRedirect();

        $content = Chapter::where('title', 'Phishing')->value('content');

        $this->assertStringNotContainsString('script', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringNotContainsString('javascript:', $content);
        $this->assertStringContainsString('href="https://example.com"', $content);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $content);
    }

    public function test_chapter_cannot_be_put_into_module_of_another_course(): void
    {
        $course = Course::factory()->by($this->teacher)->create();
        $foreignModule = Course::factory()->create()->modules()->create(['title' => 'X', 'position' => 0]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.courses.chapters.store', $course), ['title' => 'X', 'module_id' => $foreignModule->id])
            ->assertSessionHasErrors('module_id');
    }

    public function test_course_pages_render(): void
    {
        $course = Course::factory()->by($this->teacher)->create();
        $chapter = Chapter::factory()->forCourse($course)->create();
        $chapter->materials()->create(['type' => 'youtube', 'title' => 'Video', 'url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $chapter->materials()->create(['type' => 'link', 'title' => 'Odkaz', 'url' => 'https://example.com']);

        foreach ([
            route('teacher.courses.index'),
            route('teacher.courses.create'),
            route('teacher.courses.show', $course),
            route('teacher.courses.edit', $course),
            route('teacher.courses.assignments', $course),
            route('teacher.courses.chapters.create', $course),
            route('teacher.courses.chapters.edit', [$course, $chapter]),
            route('courses.show', $course),
            route('chapters.show', [$course, $chapter]),
        ] as $url) {
            $this->actingAs($this->teacher)->get($url)->assertOk();
        }
    }

    public function test_chapter_route_is_scoped_to_its_course(): void
    {
        $course = Course::factory()->by($this->teacher)->create();
        $foreignChapter = Chapter::factory()->create();

        $this->actingAs($this->teacher)
            ->get(route('teacher.courses.chapters.edit', [$course, $foreignChapter]))
            ->assertNotFound();
    }
}
