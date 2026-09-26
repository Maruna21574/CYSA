<?php

namespace Tests\Feature;

use App\Enums\MaterialType;
use App\Livewire\Teacher\ChapterMaterials;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Chapter $chapter;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('materials');
        $this->course = Course::factory()->published()->create();
        $this->teacher = $this->course->author;
        $this->chapter = Chapter::factory()->forCourse($this->course)->create();
    }

    public function test_teacher_uploads_pdf_with_random_file_name(): void
    {
        $pdf = UploadedFile::fake()->createWithContent('Phishing ../../evil.pdf', "%PDF-1.4\n%âãÏÓ\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

        Livewire::actingAs($this->teacher)
            ->test(ChapterMaterials::class, ['chapter' => $this->chapter])
            ->set('upload', $pdf)
            ->call('saveUpload')
            ->assertHasNoErrors();

        $material = Material::firstOrFail();

        $this->assertSame(MaterialType::Document, $material->type);
        $this->assertSame('application/pdf', $material->mime_type);
        $this->assertMatchesRegularExpression('~^courses/\d+/chapters/\d+/[0-9a-f-]{36}\.pdf$~', $material->path);
        Storage::disk('materials')->assertExists($material->path);
    }

    public function test_dangerous_files_are_rejected(): void
    {
        $files = [
            UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]);'),
            UploadedFile::fake()->createWithContent('image.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
            UploadedFile::fake()->createWithContent('page.html', '<html><script>alert(1)</script></html>'),
            // Allowed extension, but the content is not a PDF.
            UploadedFile::fake()->createWithContent('fake.pdf', '<?php echo 1;'),
        ];

        foreach ($files as $file) {
            Livewire::actingAs($this->teacher)
                ->test(ChapterMaterials::class, ['chapter' => $this->chapter])
                ->set('upload', $file)
                ->call('saveUpload')
                ->assertHasErrors('upload');
        }

        $this->assertSame(0, Material::count());
    }

    public function test_youtube_link_is_recognized(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(ChapterMaterials::class, ['chapter' => $this->chapter])
            ->set('linkUrl', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10')
            ->call('saveLink')
            ->assertHasNoErrors();

        $material = Material::firstOrFail();

        $this->assertSame(MaterialType::YouTube, $material->type);
        $this->assertSame('dQw4w9WgXcQ', $material->youtubeId());
    }

    public function test_javascript_links_are_rejected(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(ChapterMaterials::class, ['chapter' => $this->chapter])
            ->set('linkUrl', 'javascript:alert(1)')
            ->call('saveLink')
            ->assertHasErrors('linkUrl');
    }

    public function test_material_download_requires_access_to_the_course(): void
    {
        Storage::disk('materials')->put('courses/1/test.pdf', '%PDF-1.4 test');
        $material = $this->chapter->materials()->create([
            'type' => MaterialType::Document, 'title' => 'Príručka', 'disk' => 'materials',
            'path' => 'courses/1/test.pdf', 'mime_type' => 'application/pdf', 'size' => 13, 'original_name' => 'prirucka.pdf',
        ]);

        $assigned = User::factory()->student()->for($this->course->school)->create();
        $this->course->assignments()->create(['user_id' => $assigned->id]);
        $unassigned = User::factory()->student()->for($this->course->school)->create();
        $foreignTeacher = User::factory()->teacher()->create();

        $this->get(route('materials.show', $material))->assertRedirect(route('login'));
        $this->actingAs($unassigned)->get(route('materials.show', $material))->assertForbidden();
        $this->actingAs($foreignTeacher)->get(route('materials.show', $material))->assertForbidden();

        $this->actingAs($assigned)
            ->get(route('materials.show', [$material, 'download' => 1]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload('prirucka.pdf');
    }

    public function test_teacher_of_another_course_cannot_manage_materials(): void
    {
        $otherTeacher = User::factory()->teacher()->for($this->course->school)->create();

        Livewire::actingAs($otherTeacher)
            ->test(ChapterMaterials::class, ['chapter' => $this->chapter])
            ->assertForbidden();
    }
}
