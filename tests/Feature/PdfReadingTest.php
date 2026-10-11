<?php

namespace Tests\Feature;

use App\Enums\MaterialType;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfReadingTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Chapter $chapter;

    private Material $pdf;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('materials');
        $this->course = Course::factory()->published()->create();
        $this->chapter = Chapter::factory()->forCourse($this->course)->create();
        $this->student = User::factory()->student()->for($this->course->school)->create();
        $this->course->assignments()->create(['user_id' => $this->student->id]);
        Storage::disk('materials')->put('courses/test.pdf', '%PDF-1.4 test');
        $this->pdf = $this->chapter->materials()->create([
            'type' => MaterialType::Document,
            'title' => 'Príručka',
            'disk' => 'materials',
            'path' => 'courses/test.pdf',
            'mime_type' => 'application/pdf',
            'page_count' => 3,
            'size' => 13,
            'original_name' => 'prirucka.pdf',
        ]);
    }

    public function test_student_must_read_every_pdf_page_before_completing_chapter(): void
    {
        $this->actingAs($this->student)
            ->post(route('student.chapters.complete', [$this->course, $this->chapter]))
            ->assertStatus(422);

        $this->actingAs($this->student)
            ->postJson(route('materials.progress', $this->pdf), ['page' => 2])
            ->assertStatus(422);

        $this->actingAs($this->student)
            ->postJson(route('materials.progress', $this->pdf), ['page' => 1])
            ->assertOk()
            ->assertJson(['current_page' => 1, 'page_count' => 3, 'completed' => false]);

        $this->actingAs($this->student)
            ->postJson(route('materials.progress', $this->pdf), ['page' => 3])
            ->assertStatus(422);

        $this->actingAs($this->student)->postJson(route('materials.progress', $this->pdf), ['page' => 2])->assertOk();
        $this->actingAs($this->student)
            ->postJson(route('materials.progress', $this->pdf), ['page' => 3])
            ->assertOk()
            ->assertJson(['current_page' => 3, 'page_count' => 3, 'completed' => true]);

        $this->assertDatabaseHas('material_progress', [
            'user_id' => $this->student->id,
            'material_id' => $this->pdf->id,
            'current_page' => 3,
        ]);

        $this->actingAs($this->student)
            ->post(route('student.chapters.complete', [$this->course, $this->chapter]))
            ->assertRedirect(route('courses.show', $this->course));
    }

    public function test_chapter_renders_the_page_by_page_pdf_reader(): void
    {
        $this->actingAs($this->student)
            ->get(route('chapters.show', [$this->course, $this->chapter]))
            ->assertOk()
            ->assertSee('Ďalšia strana')
            ->assertSee('totalPages: 3', false);
    }

    public function test_only_assigned_students_can_record_pdf_progress(): void
    {
        $foreignStudent = User::factory()->student()->for($this->course->school)->create();

        $this->actingAs($foreignStudent)
            ->postJson(route('materials.progress', $this->pdf), ['page' => 1])
            ->assertForbidden();
    }
}
