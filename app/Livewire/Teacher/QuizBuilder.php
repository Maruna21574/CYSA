<?php

namespace App\Livewire\Teacher;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Topic;
use App\Support\Positioning;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Questions of a quiz: ordering, per-quiz points and picking questions from the bank.
 */
class QuizBuilder extends Component
{
    public Quiz $quiz;

    public string $search = '';

    public string $topic = '';

    public string $type = '';

    public bool $onlyThisCourse = true;

    public function mount(Quiz $quiz): void
    {
        $this->authorize('update', $quiz);
        $this->quiz = $quiz;
    }

    public function add(int $questionId): void
    {
        $this->authorize('update', $this->quiz);

        $question = $this->bankQuery()->findOrFail($questionId);

        $this->quiz->questions()->syncWithoutDetaching([$question->id => [
            'position' => Positioning::next(DB::table('quiz_question')->where('quiz_id', $this->quiz->id)),
        ]]);

        unset($this->bank);
        $this->quiz->touch();
    }

    public function remove(int $questionId): void
    {
        $this->authorize('update', $this->quiz);

        $this->quiz->questions()->detach($questionId);
        $this->quiz->touch();
    }

    /**
     * wire:sort handler. Positions live on the pivot table, so they are rewritten directly.
     */
    public function sortQuestion(int $questionId, int $position): void
    {
        $this->authorize('update', $this->quiz);

        $ids = $this->quiz->questions()->pluck('questions.id')->reject(fn (int $id): bool => $id === $questionId)->values()->all();
        abort_unless($this->quiz->questions()->whereKey($questionId)->exists(), 404);

        array_splice($ids, max(0, min($position, count($ids))), 0, [$questionId]);

        DB::transaction(function () use ($ids): void {
            foreach ($ids as $index => $id) {
                $this->quiz->questions()->updateExistingPivot($id, ['position' => $index]);
            }
        });
    }

    public function setPoints(int $questionId, string $points): void
    {
        $this->authorize('update', $this->quiz);

        if ($points !== '' && (! is_numeric($points) || (float) $points < 0 || (float) $points > 1000)) {
            $this->dispatch('toast', type: 'error', message: __('Body musia byť číslo od 0 do 1000.'));

            return;
        }

        abort_unless($this->quiz->questions()->whereKey($questionId)->exists(), 404);
        $this->quiz->questions()->updateExistingPivot($questionId, ['points' => $points === '' ? null : (float) $points]);
    }

    /**
     * Approved questions of the teacher's bank that are not in the quiz yet.
     *
     * @return Collection<int, Question>
     */
    #[Computed]
    public function bank(): Collection
    {
        $like = '%'.addcslashes(trim($this->search), '%_\\').'%';

        return $this->bankQuery()
            ->whereNotIn('id', $this->quiz->questions()->select('questions.id'))
            ->when($this->onlyThisCourse, fn ($query) => $query->where(fn ($query) => $query->where('course_id', $this->quiz->course_id)->orWhereNull('course_id')))
            ->when(trim($this->search) !== '', fn ($query) => $query->where('body', 'like', $like))
            ->when(QuestionType::tryFrom($this->type), fn ($query, QuestionType $type) => $query->where('type', $type))
            ->when($this->topic !== '', fn ($query) => $query->whereHas('topics', fn ($query) => $query->whereKey((int) $this->topic)))
            ->with('topics:id,name')
            ->latest()
            ->limit(15)
            ->get();
    }

    /**
     * @return Builder<Question>
     */
    private function bankQuery(): Builder
    {
        return Question::query()
            ->manageableBy(auth()->user())
            ->where('school_id', $this->quiz->school_id)
            ->approved();
    }

    public function render(): View
    {
        $questions = $this->quiz->questions()->with('topics:id,name')->get();

        return view('livewire.teacher.quiz-builder', [
            'questions' => $questions,
            'totalPoints' => $questions->sum(fn (Question $question): float => Quiz::pointsFor($question)),
            'topics' => Topic::options(),
        ]);
    }
}
