<?php

namespace App\Livewire\Teacher;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use App\Rules\AllowedMaterialFile;
use App\Services\Files\MaterialStorage;
use App\Services\Questions\QuestionService;
use App\Services\Questions\QuestionValidator;
use App\Support\Positioning;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Create / edit a question of any type. When opened from a quiz (?quiz=ID), a new question
 * is attached to that quiz after saving.
 */
class QuestionEditor extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $questionId = null;

    #[Locked]
    public ?int $quizId = null;

    public string $type = 'single_choice';

    public string $body = '';

    public string $explanation = '';

    public string $defaultPoints = '1';

    public string $difficulty = '';

    public string $courseId = '';

    public string $chapterId = '';

    /** @var list<int|string> */
    public array $topicIds = [];

    public string $tags = '';

    /** @var list<array{id: int|null, body: string, match_body: string, blank_index: int|null, is_correct: bool}> */
    public array $options = [];

    public bool $caseSensitive = false;

    public bool $partialCredit = false;

    /** @var TemporaryUploadedFile|null */
    public $image = null;

    public bool $removeImage = false;

    #[Locked]
    public ?string $currentImage = null;

    public function mount(?Question $question = null): void
    {
        if ($question?->exists) {
            $this->authorize('update', $question);
            $this->fillFrom($question);
        } else {
            $this->authorize('create', Question::class);
            $this->options = QuestionService::defaultOptions(QuestionType::SingleChoice);
        }

        $quiz = request()->integer('quiz') ? Quiz::find(request()->integer('quiz')) : null;
        $this->quizId = $quiz && auth()->user()->can('update', $quiz) ? $quiz->id : null;

        if ($quiz && ! $question?->exists && $this->courseId === '') {
            $this->courseId = (string) $quiz->course_id;
        }
    }

    public function updatedType(string $value): void
    {
        $new = QuestionType::tryFrom($value) ?? QuestionType::SingleChoice;
        $this->type = $new->value;

        // Choice questions keep their options when switching between single and multiple choice.
        $keep = in_array($new, [QuestionType::SingleChoice, QuestionType::MultipleChoice], true)
            && count($this->options) >= 2
            && ! collect($this->options)->contains(fn (array $option): bool => ($option['blank_index'] ?? null) !== null);

        if (! $keep) {
            $this->options = QuestionService::defaultOptions($new);
        } elseif ($new === QuestionType::SingleChoice) {
            $first = collect($this->options)->search(fn (array $option): bool => $option['is_correct']);
            $this->options = collect($this->options)->map(fn (array $option, int $i): array => [...$option, 'is_correct' => $i === $first])->all();
        }
    }

    public function updatedCourseId(): void
    {
        $this->chapterId = '';
    }

    public function addOption(): void
    {
        if (count($this->options) >= QuestionValidator::MAX_OPTIONS) {
            return;
        }

        $blank = $this->type === QuestionType::FillBlank->value ? (max($this->blankIndexes ?: [1])) : null;

        $this->options[] = ['id' => null, 'body' => '', 'match_body' => '', 'blank_index' => $blank, 'is_correct' => $this->type !== QuestionType::SingleChoice->value && $this->type !== QuestionType::MultipleChoice->value];
    }

    public function removeOption(int $index): void
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    /**
     * Single choice / true-false: exactly one option is correct.
     */
    public function markCorrect(int $index): void
    {
        foreach ($this->options as $i => $option) {
            $this->options[$i]['is_correct'] = $i === $index;
        }
    }

    public function save(QuestionService $service, QuestionValidator $structure, MaterialStorage $storage): void
    {
        $existing = $this->questionId ? Question::findOrFail($this->questionId) : null;
        $existing ? $this->authorize('update', $existing) : $this->authorize('create', Question::class);

        $this->validate($this->rules(), attributes: $this->validationAttributes());

        $type = QuestionType::from($this->type);

        foreach ($structure->validate($type, $this->body, $this->options) as $field => $message) {
            $this->addError($field, $message);
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $course = $this->courseId !== '' ? Course::manageableBy(auth()->user())->findOrFail((int) $this->courseId) : null;
        $chapter = $course && $this->chapterId !== '' ? $course->chapters()->findOrFail((int) $this->chapterId) : null;

        $question = $existing ?? new Question;

        if (! $question->exists) {
            $question->school_id = auth()->user()->school_id;
            $question->author_id = auth()->id();
        }

        $service->save($question, [
            'course_id' => $course?->id,
            'chapter_id' => $chapter?->id,
            'type' => $type,
            'body' => trim($this->body),
            'explanation' => trim($this->explanation) ?: null,
            'default_points' => (float) $this->defaultPoints,
            'difficulty' => $this->difficulty ?: null,
            'settings' => array_filter([
                'case_sensitive' => $type === QuestionType::ShortAnswer || $type === QuestionType::FillBlank ? $this->caseSensitive : null,
                'partial_credit' => in_array($type, [QuestionType::MultipleChoice, QuestionType::FillBlank, QuestionType::Matching], true) ? $this->partialCredit : null,
            ], fn ($value): bool => $value !== null),
        ], $this->options, array_map('intval', $this->topicIds), $this->tags);

        $this->saveImage($question, $storage);

        if ($this->quizId && ! $existing) {
            $quiz = Quiz::findOrFail($this->quizId);
            $this->authorize('update', $quiz);
            $quiz->questions()->syncWithoutDetaching([$question->id => [
                'position' => Positioning::next(DB::table('quiz_question')->where('quiz_id', $quiz->id)),
            ]]);
        }

        session()->flash('success', $existing ? __('Otázka bola uložená.') : __('Otázka bola vytvorená.'));

        $this->redirectRoute($this->quizId ? 'teacher.quizzes.show' : 'teacher.questions.index', $this->quizId ? ['quiz' => $this->quizId] : []);
    }

    /**
     * Blank numbers currently used in the body (fill in the blank).
     *
     * @return list<int>
     */
    #[Computed]
    public function blankIndexes(): array
    {
        return Question::blankIndexes($this->body);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'body' => ['required', 'string', 'max:5000'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'defaultPoints' => ['required', 'numeric', 'min:0', 'max:1000'],
            'difficulty' => ['nullable', Rule::enum(Difficulty::class)],
            'courseId' => ['nullable', 'integer'],
            'chapterId' => ['nullable', 'integer'],
            'topicIds' => ['array'],
            'topicIds.*' => ['integer', Rule::exists('topics', 'id')],
            'tags' => ['nullable', 'string', 'max:500'],
            'options' => ['array', 'max:'.QuestionValidator::MAX_OPTIONS],
            'options.*.body' => ['nullable', 'string', 'max:1000'],
            'options.*.match_body' => ['nullable', 'string', 'max:1000'],
            'options.*.blank_index' => ['nullable', 'integer', 'between:1,20'],
            'options.*.is_correct' => ['boolean'],
            'caseSensitive' => ['boolean'],
            'partialCredit' => ['boolean'],
            'image' => ['nullable', 'max:5120', AllowedMaterialFile::imagesOnly()],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'body' => __('znenie otázky'),
            'explanation' => __('vysvetlenie'),
            'defaultPoints' => __('body'),
            'difficulty' => __('obtiažnosť'),
            'options.*.body' => __('možnosť'),
            'options.*.match_body' => __('pravá strana'),
            'image' => __('obrázok'),
        ];
    }

    private function saveImage(Question $question, MaterialStorage $storage): void
    {
        if (! $this->image && ! $this->removeImage) {
            return;
        }

        $storage->delete($question->image_path);
        $path = $this->image ? $storage->storeQuestionImage($this->image, $question) : null;
        $question->forceFill(['image_path' => $path])->save();
    }

    private function fillFrom(Question $question): void
    {
        $this->questionId = $question->id;
        $this->type = $question->type->value;
        $this->body = $question->body;
        $this->explanation = (string) $question->explanation;
        $this->defaultPoints = rtrim(rtrim((string) $question->default_points, '0'), '.');
        $this->difficulty = $question->difficulty?->value ?? '';
        $this->courseId = (string) ($question->course_id ?? '');
        $this->chapterId = (string) ($question->chapter_id ?? '');
        $this->topicIds = $question->topics()->pluck('topics.id')->map(fn ($id): string => (string) $id)->all();
        $this->tags = $question->tags()->pluck('name')->implode(', ');
        $this->caseSensitive = (bool) $question->setting('case_sensitive', false);
        $this->partialCredit = (bool) $question->setting('partial_credit', false);
        $this->currentImage = $question->image_path;
        $this->options = $question->options->map(fn ($option): array => [
            'id' => $option->id,
            'body' => $option->body,
            'match_body' => (string) $option->match_body,
            'blank_index' => $option->blank_index,
            'is_correct' => $option->is_correct,
        ])->all();
    }

    public function render(): View
    {
        $courses = Course::manageableBy(auth()->user())->orderBy('title')->pluck('title', 'id')->all();
        $chapters = $this->courseId !== '' && isset($courses[(int) $this->courseId])
            ? Course::find((int) $this->courseId)->orderedChapters()->pluck('title', 'id')->all()
            : [];

        return view('livewire.teacher.question-editor', [
            'types' => QuestionType::cases(),
            'currentType' => QuestionType::tryFrom($this->type) ?? QuestionType::SingleChoice,
            'courses' => $courses,
            'chapters' => $chapters,
            'topics' => Topic::options(),
            'quiz' => $this->quizId ? Quiz::find($this->quizId) : null,
        ])->title($this->questionId ? __('Úprava otázky') : __('Nová otázka'));
    }
}
