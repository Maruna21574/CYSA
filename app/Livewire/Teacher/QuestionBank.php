<?php

namespace App\Livewire\Teacher;

use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\Question;
use App\Models\Topic;
use App\Services\Files\MaterialStorage;
use App\Services\Questions\QuestionService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class QuestionBank extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $topic = '';

    #[Url(except: '')]
    public string $course = '';

    #[Url(except: '')]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Question::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'type', 'topic', 'course', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function duplicate(int $questionId, QuestionService $service): void
    {
        $question = Question::manageableBy(auth()->user())->findOrFail($questionId);
        $this->authorize('update', $question);

        $copy = $service->duplicate($question->load('options'));

        $this->redirectRoute('teacher.questions.edit', $copy);
    }

    public function delete(int $questionId, MaterialStorage $storage): void
    {
        $question = Question::manageableBy(auth()->user())->findOrFail($questionId);
        $this->authorize('delete', $question);

        if ($question->quizzes()->exists()) {
            $this->dispatch('toast', type: 'error', message: __('Otázka je použitá v teste. Najprv ju z testu odstráňte.'));

            return;
        }

        $question->delete();
        $storage->delete($question->image_path);

        $this->dispatch('toast', message: __('Otázka bola odstránená.'));
    }

    public function render(): View
    {
        $like = '%'.addcslashes(trim($this->search), '%_\\').'%';

        $questions = Question::query()
            ->manageableBy(auth()->user())
            ->when(trim($this->search) !== '', fn ($query) => $query->where('body', 'like', $like))
            ->when(QuestionType::tryFrom($this->type), fn ($query, QuestionType $type) => $query->where('type', $type))
            ->when(QuestionStatus::tryFrom($this->status), fn ($query, QuestionStatus $status) => $query->where('status', $status))
            ->when($this->topic !== '', fn ($query) => $query->whereHas('topics', fn ($query) => $query->whereKey((int) $this->topic)))
            ->when($this->course !== '', fn ($query) => $query->where('course_id', (int) $this->course))
            ->with(['topics:id,name', 'course:id,title'])
            ->withCount('quizzes')
            ->latest()
            ->paginate(20);

        return view('livewire.teacher.question-bank', [
            'questions' => $questions,
            'topics' => Topic::options(),
            'courses' => Course::manageableBy(auth()->user())->orderBy('title')->pluck('title', 'id')->all(),
        ])->title(__('Banka otázok'));
    }
}
