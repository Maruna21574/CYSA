<?php

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Livewire\Teacher\QuestionBank;
use App\Livewire\Teacher\QuestionEditor;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->create();
    }

    public function test_teacher_creates_single_choice_question_with_topics_and_tags(): void
    {
        $topic = Topic::where('slug', 'phishing')->firstOrFail();

        Livewire::actingAs($this->teacher)
            ->test(QuestionEditor::class)
            ->set('body', 'Ktorý znak najčastejšie prezrádza phishing?')
            ->set('options.0.body', 'Naliehavá výzva a podozrivý odkaz')
            ->set('options.1.body', 'Správa od kamaráta v škole')
            ->call('markCorrect', 0)
            ->set('topicIds', [(string) $topic->id])
            ->set('tags', 'e-mail, podvod, E-mail')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('teacher.questions.index'));

        $question = Question::firstOrFail();

        $this->assertSame($this->teacher->id, $question->author_id);
        $this->assertSame(2, $question->options()->count());
        $this->assertTrue($question->options()->first()->is_correct);
        $this->assertSame([$topic->id], $question->topics()->pluck('topics.id')->all());
        $this->assertSame(2, $question->tags()->count());
    }

    public function test_structural_errors_are_reported(): void
    {
        Livewire::actingAs($this->teacher)
            ->test(QuestionEditor::class)
            ->set('body', 'Otázka bez správnej odpovede')
            ->set('options.0.body', 'A')
            ->set('options.1.body', 'B')
            ->set('options.0.is_correct', false)
            ->call('save')
            ->assertHasErrors('options');

        $this->assertSame(0, Question::count());
    }

    public function test_each_question_type_can_be_saved(): void
    {
        $cases = [
            'true_false' => fn ($c) => $c->set('body', 'Heslo 123456 je bezpečné.')->call('markCorrect', 1),
            'short_answer' => fn ($c) => $c->set('body', 'Ako sa volá podvodný e-mail?')->set('options.0.body', 'phishing'),
            'fill_blank' => fn ($c) => $c->set('body', 'Silné heslo má aspoň [[1]] znakov.')->set('options.0.body', '12'),
            'matching' => fn ($c) => $c->set('body', 'Priraď pojmy')
                ->set('options.0.body', 'Phishing')->set('options.0.match_body', 'Podvodná správa')
                ->set('options.1.body', '2FA')->set('options.1.match_body', 'Druhý krok overenia'),
            'multiple_choice' => fn ($c) => $c->set('body', 'Čo patrí k silnému heslu?')
                ->set('options.0.body', 'Dĺžka')->set('options.0.is_correct', true)
                ->set('options.1.body', 'Rôzne znaky')->set('options.1.is_correct', true)
                ->set('options.2.body', 'Meno psa'),
        ];

        foreach ($cases as $type => $fill) {
            $component = Livewire::actingAs($this->teacher)->test(QuestionEditor::class)->set('type', $type);
            $fill($component)->call('save')->assertHasNoErrors();
        }

        $this->assertSame(count($cases), Question::count());
        $this->assertSame(QuestionType::Matching, Question::where('type', 'matching')->first()->type);
        $this->assertSame(1, Question::where('type', 'fill_blank')->first()->options()->first()->blank_index);
    }

    public function test_editing_keeps_option_ids(): void
    {
        $question = Question::factory()->by($this->teacher)->create();
        $ids = $question->options()->pluck('id')->all();

        Livewire::actingAs($this->teacher)
            ->test(QuestionEditor::class, ['question' => $question])
            ->set('options.1.body', 'Upravená možnosť')
            ->call('removeOption', 3)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(array_slice($ids, 0, 3), $question->options()->pluck('id')->all());
        $this->assertSame('Upravená možnosť', $question->options()->find($ids[1])->body);
    }

    public function test_teacher_cannot_edit_question_of_colleague(): void
    {
        $question = Question::factory()->create();

        Livewire::actingAs($this->teacher)
            ->test(QuestionEditor::class, ['question' => $question])
            ->assertForbidden();
    }

    public function test_question_cannot_be_linked_to_foreign_course(): void
    {
        $foreignCourse = Course::factory()->create();

        Livewire::actingAs($this->teacher)
            ->test(QuestionEditor::class)
            ->set('body', 'Otázka?')
            ->set('options.0.body', 'A')
            ->set('options.1.body', 'B')
            ->call('markCorrect', 0)
            ->set('courseId', (string) $foreignCourse->id)
            ->call('save')
            ->assertNotFound();
    }

    public function test_question_used_in_quiz_cannot_be_deleted(): void
    {
        $course = Course::factory()->by($this->teacher)->create();
        $quiz = Quiz::factory()->forCourse($course)->create();
        $question = Question::factory()->by($this->teacher)->create();
        $quiz->questions()->attach($question->id, ['position' => 0]);

        Livewire::actingAs($this->teacher)
            ->test(QuestionBank::class)
            ->call('delete', $question->id)
            ->assertDispatched('toast');

        $this->assertNotSoftDeleted($question);
    }

    public function test_new_question_created_from_quiz_is_attached(): void
    {
        $course = Course::factory()->by($this->teacher)->create();
        $quiz = Quiz::factory()->forCourse($course)->create();

        Livewire::withQueryParams(['quiz' => $quiz->id])
            ->actingAs($this->teacher)
            ->test(QuestionEditor::class)
            ->set('body', 'Otázka z testu?')
            ->set('options.0.body', 'A')
            ->set('options.1.body', 'B')
            ->call('markCorrect', 0)
            ->call('save')
            ->assertRedirect(route('teacher.quizzes.show', $quiz));

        $this->assertSame(1, $quiz->questions()->count());
        $this->assertSame($course->id, Question::first()->course_id);
    }

    public function test_bank_lists_only_own_questions(): void
    {
        Question::factory()->by($this->teacher)->create(['body' => 'Moja otázka?']);
        Question::factory()->create(['body' => 'Cudzia otázka?']);

        Livewire::actingAs($this->teacher)
            ->test(QuestionBank::class)
            ->assertSee('Moja otázka?')
            ->assertDontSee('Cudzia otázka?');
    }
}
