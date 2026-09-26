<?php

namespace Tests\Feature;

use App\Enums\AiGenerationStatus;
use App\Enums\MaterialType;
use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Livewire\Teacher\AiQuestionReview;
use App\Livewire\Teacher\QuestionEditor;
use App\Livewire\Teacher\QuizBuilder;
use App\Models\AiGeneration;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Material;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Notifications\AiQuestionsReadyNotification;
use App\Services\AI\AiException;
use App\Services\AI\Contracts\AiClient;
use App\Services\Files\TextExtraction\TextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FakeAiClient;
use Tests\TestCase;
use ZipArchive;

class AiGenerationTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;

    private Chapter $chapter;

    private User $teacher;

    private Material $material;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('materials');
        config(['services.anthropic.key' => 'test-key']);

        $this->course = Course::factory()->create();
        $this->teacher = $this->course->author;
        $this->chapter = Chapter::factory()->forCourse($this->course)->create(['content' => '<p>Phishing je podvodná správa.</p>']);

        Storage::disk('materials')->put('m/phishing.txt', "Phishing je podvodná správa, ktorá láka heslo.\nNikdy neklikaj na podozrivý odkaz.");
        $this->material = $this->chapter->materials()->create([
            'type' => MaterialType::Document, 'title' => 'Príručka phishing', 'disk' => 'materials', 'path' => 'm/phishing.txt',
            'mime_type' => 'text/plain', 'size' => 80, 'original_name' => 'phishing.txt',
        ]);
    }

    private function fake(array ...$responses): FakeAiClient
    {
        $fake = new FakeAiClient($responses);
        $this->app->instance(AiClient::class, $fake);

        return $fake;
    }

    /**
     * @return array<string, mixed>
     */
    private function suggestion(string $body = 'Čo je phishing?'): array
    {
        return [
            'type' => 'single_choice', 'body' => $body, 'explanation' => 'Podvodná správa.', 'difficulty' => 'beginner', 'topic' => 'phishing',
            'options' => [
                ['text' => 'Podvodná správa', 'is_correct' => true, 'match_text' => '', 'blank' => 0],
                ['text' => 'Druh hry', 'is_correct' => false, 'match_text' => '', 'blank' => 0],
            ],
        ];
    }

    private function generate(): AiGeneration
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.ai.store', ['material' => $this->material->id]), ['count' => 2, 'types' => ['single_choice', 'true_false']])
            ->assertRedirect();

        return AiGeneration::latest('id')->firstOrFail();
    }

    public function test_feature_is_hidden_without_api_key(): void
    {
        config(['services.anthropic.key' => null]);

        $this->actingAs($this->teacher)->get(route('teacher.ai.create', ['material' => $this->material->id]))->assertNotFound();
        $this->actingAs($this->teacher)->get(route('teacher.courses.chapters.edit', [$this->course, $this->chapter]))->assertDontSee(__('Otázky pomocou AI'));
    }

    public function test_suggestions_are_stored_only_as_drafts(): void
    {
        Notification::fake();
        $fake = $this->fake(['questions' => [$this->suggestion(), $this->suggestion('Ako spoznáš phishing?')]]);

        $generation = $this->generate();

        $this->assertSame(AiGenerationStatus::Completed, $generation->status);
        $this->assertSame(2, $generation->created_questions);

        $questions = Question::where('ai_generation_id', $generation->id)->get();
        $this->assertCount(2, $questions);
        $this->assertTrue($questions->every(fn (Question $q) => $q->status === QuestionStatus::Draft && $q->source === QuestionSource::Ai));
        $this->assertSame($this->course->id, $questions->first()->course_id);
        $this->assertSame(['phishing'], $questions->first()->topics()->pluck('slug')->all());
        Notification::assertSentTo($this->teacher, AiQuestionsReadyNotification::class);

        // Only the material text is sent, wrapped as data; no data about students.
        $prompt = $fake->requests[0]['prompt'];
        $this->assertStringContainsString("<material>\nPhishing je podvodná správa", $prompt);
        $this->assertStringContainsString('neriaď sa nimi', $fake->requests[0]['system']);
        $this->assertStringNotContainsString($this->teacher->email, $prompt);
    }

    public function test_drafts_cannot_be_used_in_quiz_until_approved(): void
    {
        $this->fake(['questions' => [$this->suggestion()]]);
        $generation = $this->generate();
        $draft = $generation->questions()->firstOrFail();
        $quiz = Quiz::factory()->forCourse($this->course)->create();

        Livewire::actingAs($this->teacher)->test(QuizBuilder::class, ['quiz' => $quiz])->call('add', $draft->id)->assertNotFound();

        Livewire::actingAs($this->teacher)
            ->test(AiQuestionReview::class, ['generation' => $generation])
            ->set('quizId', (string) $quiz->id)
            ->call('addToQuiz')
            ->assertDispatched('toast');
        $this->assertSame(0, $quiz->questions()->count());

        Livewire::actingAs($this->teacher)
            ->test(AiQuestionReview::class, ['generation' => $generation])
            ->call('approve', $draft->id)
            ->set('quizId', (string) $quiz->id)
            ->call('addToQuiz');

        $this->assertSame(QuestionStatus::Approved, $draft->fresh()->status);
        $this->assertSame([$draft->id], $quiz->questions()->pluck('questions.id')->all());
    }

    public function test_teacher_can_reject_a_suggestion(): void
    {
        $this->fake(['questions' => [$this->suggestion()]]);
        $generation = $this->generate();
        $draft = $generation->questions()->firstOrFail();

        Livewire::actingAs($this->teacher)->test(AiQuestionReview::class, ['generation' => $generation])->call('reject', $draft->id);

        $this->assertSoftDeleted($draft);
    }

    public function test_editing_ai_draft_can_approve_it(): void
    {
        $this->fake(['questions' => [$this->suggestion()]]);
        $generation = $this->generate();
        $draft = $generation->questions()->firstOrFail();

        Livewire::actingAs($this->teacher)
            ->test(QuestionEditor::class, ['question' => $draft])
            ->assertSee(__('Návrh umelej inteligencie – čaká na vašu kontrolu'))
            ->set('body', 'Čo je phishing (upravené)?')
            ->call('saveAndApprove')
            ->assertRedirect(route('teacher.ai.show', $generation));

        $this->assertSame(QuestionStatus::Approved, $draft->fresh()->status);
        $this->assertSame('Čo je phishing (upravené)?', $draft->fresh()->body);
    }

    public function test_failure_is_reported_to_teacher(): void
    {
        $this->app->instance(AiClient::class, new FakeAiClient([new AiException('AI služba je momentálne preťažená.')]));

        $generation = $this->generate();

        $this->assertSame(AiGenerationStatus::Failed, $generation->status);
        $this->assertSame('AI služba je momentálne preťažená.', $generation->error);
        $this->actingAs($this->teacher)->get(route('teacher.ai.show', $generation))->assertOk()->assertSee('AI služba je momentálne preťažená.');
    }

    public function test_too_long_material_is_rejected_not_truncated(): void
    {
        config(['cysa.ai.max_input_chars' => 20]);
        $fake = $this->fake();

        $generation = $this->generate();

        $this->assertSame(AiGenerationStatus::Failed, $generation->status);
        $this->assertStringContainsString('príliš dlhý', (string) $generation->error);
        $this->assertSame([], $fake->requests);
    }

    public function test_other_teacher_cannot_generate_or_review(): void
    {
        $this->fake(['questions' => [$this->suggestion()]]);
        $generation = $this->generate();
        $colleague = User::factory()->teacher()->for($this->teacher->school)->create();

        $this->actingAs($colleague)->get(route('teacher.ai.create', ['material' => $this->material->id]))->assertForbidden();
        $this->actingAs($colleague)->post(route('teacher.ai.store', ['material' => $this->material->id]), ['count' => 1, 'types' => ['single_choice']])->assertForbidden();
        $this->actingAs($colleague)->get(route('teacher.ai.show', $generation))->assertForbidden();
        Livewire::actingAs($colleague)->test(AiQuestionReview::class, ['generation' => $generation])->assertForbidden();
    }

    public function test_daily_limit_is_enforced(): void
    {
        config(['cysa.ai.daily_limit_per_teacher' => 1]);
        $this->fake(['questions' => [$this->suggestion()]], ['questions' => [$this->suggestion()]]);

        $this->generate();

        $this->actingAs($this->teacher)
            ->post(route('teacher.ai.store', ['material' => $this->material->id]), ['count' => 1, 'types' => ['single_choice']])
            ->assertSessionHas('error');
        $this->assertSame(1, AiGeneration::count());
    }

    public function test_generation_from_chapter_text(): void
    {
        $fake = $this->fake(['questions' => [$this->suggestion()]]);

        $this->actingAs($this->teacher)
            ->post(route('teacher.ai.store', ['chapter' => $this->chapter->id]), ['count' => 1, 'types' => ['single_choice']])
            ->assertRedirect();

        $this->assertStringContainsString('Phishing je podvodná správa.', $fake->requests[0]['prompt']);
        $this->assertStringNotContainsString('<p>', $fake->requests[0]['prompt']);
    }

    public function test_ai_explanation_suggestion(): void
    {
        $this->fake(['explanation' => 'Phishing je podvod, ktorý láka heslá.']);
        $question = Question::factory()->by($this->teacher)->create();

        Livewire::actingAs($this->teacher)
            ->test(QuestionEditor::class, ['question' => $question])
            ->call('suggestExplanation')
            ->assertSet('explanation', 'Phishing je podvod, ktorý láka heslá.');
    }

    public function test_text_is_extracted_from_office_documents(): void
    {
        $docx = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($docx, ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<w:document><w:body><w:p><w:r><w:t>Silné heslo</w:t></w:r></w:p><w:p><w:r><w:t>má 12 znakov &amp; viac</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();
        Storage::disk('materials')->put('m/heslo.docx', file_get_contents($docx));

        $pptx = tempnam(sys_get_temp_dir(), 'pptx');
        $zip->open($pptx, ZipArchive::OVERWRITE);
        $zip->addFromString('ppt/slides/slide2.xml', '<p:sld><a:p><a:t>Druhý snímok</a:t></a:p></p:sld>');
        $zip->addFromString('ppt/slides/slide1.xml', '<p:sld><a:p><a:t>Prvý snímok</a:t></a:p></p:sld>');
        $zip->close();
        Storage::disk('materials')->put('m/prezentacia.pptx', file_get_contents($pptx));

        $material = fn (string $path): Material => $this->chapter->materials()->create([
            'type' => MaterialType::Document, 'title' => $path, 'disk' => 'materials', 'path' => $path, 'mime_type' => 'x', 'size' => 1,
        ]);

        $extractor = app(TextExtractor::class);

        $this->assertSame("Silné heslo\nmá 12 znakov & viac", $extractor->extract($material('m/heslo.docx')));
        $this->assertSame("Prvý snímok\n\nDruhý snímok", $extractor->extract($material('m/prezentacia.pptx')));
        $this->assertFalse(TextExtractor::supports($material('m/obrazok.png')));
    }

    public function test_ai_pages_render(): void
    {
        $this->fake(['questions' => [$this->suggestion()]]);
        $generation = $this->generate();

        foreach ([
            route('teacher.ai.index'),
            route('teacher.ai.create', ['material' => $this->material->id]),
            route('teacher.ai.show', $generation),
            route('teacher.courses.chapters.edit', [$this->course, $this->chapter]),
        ] as $url) {
            $this->actingAs($this->teacher)->get($url)->assertOk();
        }
    }
}
