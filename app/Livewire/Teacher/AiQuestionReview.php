<?php

namespace App\Livewire\Teacher;

use App\Enums\QuestionStatus;
use App\Models\AiGeneration;
use App\Models\MaterialText;
use App\Models\Question;
use App\Models\Quiz;
use App\Services\AI\AiException;
use App\Services\AI\AIQuizGenerationService;
use App\Services\Files\TextExtraction\TextExtractor;
use App\Support\Positioning;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Teacher's review of AI suggestions: approve, edit, delete, then add to a quiz.
 * Nothing reaches students without an explicit approval.
 */
class AiQuestionReview extends Component
{
    #[Locked]
    public int $generationId;

    public string $quizId = '';

    public function mount(AiGeneration $generation): void
    {
        $this->authorize('update', $generation->course);
        $this->generationId = $generation->id;
    }

    public function approve(int $questionId): void
    {
        $this->question($questionId)->forceFill(['status' => QuestionStatus::Approved])->save();
        $this->dispatch('toast', message: __('Otázka bola schválená.'));
    }

    public function approveAll(): void
    {
        $this->generation()->questions()->where('status', QuestionStatus::Draft)->update(['status' => QuestionStatus::Approved->value]);
        $this->dispatch('toast', message: __('Všetky otázky boli schválené.'));
    }

    public function reject(int $questionId): void
    {
        $this->question($questionId)->delete();
        $this->dispatch('toast', message: __('Návrh bol zamietnutý.'));
    }

    public function addToQuiz(): void
    {
        $generation = $this->generation();
        $quiz = Quiz::manageableBy(auth()->user())->where('course_id', $generation->course_id)->findOrFail((int) $this->quizId);
        $this->authorize('update', $quiz);

        $approved = $generation->questions()->where('status', QuestionStatus::Approved)->pluck('id');

        if ($approved->isEmpty()) {
            $this->dispatch('toast', type: 'error', message: __('Najprv schváľte aspoň jednu otázku.'));

            return;
        }

        DB::transaction(function () use ($quiz, $approved): void {
            $position = Positioning::next(DB::table('quiz_question')->where('quiz_id', $quiz->id));

            foreach ($approved as $offset => $questionId) {
                $quiz->questions()->syncWithoutDetaching([$questionId => ['position' => $position + $offset]]);
            }
        });

        $this->dispatch('toast', message: __('Schválené otázky boli pridané do testu „:title“.', ['title' => $quiz->title]));
    }

    /**
     * AI summary and keywords of the source material (cached with the extracted text).
     */
    public function summarize(AIQuizGenerationService $ai, TextExtractor $extractor): void
    {
        $generation = $this->generation();
        abort_unless($generation->material !== null, 404);

        if (! RateLimiter::attempt('ai-summary:'.auth()->id(), 5, fn () => true, 60)) {
            $this->dispatch('toast', type: 'error', message: __('Príliš veľa požiadaviek. Skúste to o minútu.'));

            return;
        }

        try {
            $text = $extractor->textOf($generation->material);
            MaterialText::whereKey($generation->material_id)->update([
                'summary' => $ai->summarizeMaterial($text),
                'keywords' => json_encode($ai->generateKeywords($text), JSON_UNESCAPED_UNICODE),
            ]);
        } catch (AiException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    private function generation(): AiGeneration
    {
        $generation = AiGeneration::with('course')->findOrFail($this->generationId);
        $this->authorize('update', $generation->course);

        return $generation;
    }

    private function question(int $questionId): Question
    {
        return $this->generation()->questions()->findOrFail($questionId);
    }

    public function render(): View
    {
        $generation = $this->generation()->load(['material', 'chapter']);

        return view('livewire.teacher.ai-question-review', [
            'generation' => $generation,
            'questions' => $generation->questions()->with(['options', 'topics:id,name'])->orderBy('id')->get(),
            'quizzes' => Quiz::manageableBy(auth()->user())->where('course_id', $generation->course_id)->orderBy('title')->pluck('title', 'id')->all(),
            'materialText' => $generation->material_id ? MaterialText::find($generation->material_id) : null,
        ]);
    }
}
