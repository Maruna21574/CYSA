<?php

namespace App\Livewire\Student;

use App\Enums\QuestionType;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Services\Quiz\AttemptService;
use App\Services\Quiz\QuizUnavailableException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Taking a quiz. Security notes:
 * - public properties contain only the attempt ID (locked), the current index and the
 *   student's own responses - never the snapshots, so correct answers are not in the page;
 * - every change is saved and validated by AttemptService (time limit, closed attempt);
 * - the timer in the browser is only a convenience, the server decides.
 */
class QuizPlayer extends Component
{
    #[Locked]
    public int $attemptId;

    public int $current = 0;

    /** @var array<int|string, array<string, mixed>> question ID => response */
    public array $responses = [];

    public function mount(QuizAttempt $attempt, AttemptService $service): void
    {
        $attempt->refresh();
        $this->authorize('view', $attempt);
        $this->attemptId = $attempt->id;

        if (! $attempt->isInProgress() || $attempt->isExpired()) {
            $this->finish($attempt, $service);

            return;
        }

        $this->authorize('take', $attempt);

        foreach ($attempt->answers as $answer) {
            $this->responses[$answer->question_id] = $answer->response ?? $this->emptyResponse($answer);
        }
    }

    /**
     * Every change of an answer is persisted immediately (nothing is lost on reload or crash).
     */
    public function updatedResponses(mixed $value, string $key, AttemptService $service): void
    {
        $questionId = (int) Arr::first(explode('.', $key));

        if (! array_key_exists($questionId, $this->responses)) {
            return;
        }

        try {
            $service->saveAnswer($this->attempt(), $questionId, $this->responses[$questionId]);
        } catch (QuizUnavailableException $exception) {
            $this->finish($this->attempt(), $service, $exception->getMessage());
        }
    }

    public function goTo(int $index): void
    {
        $this->current = max(0, min($index, count($this->attempt()->question_order) - 1));
    }

    public function submit(AttemptService $service): void
    {
        $attempt = $this->attempt();
        $this->authorize('view', $attempt);

        $this->finish($attempt, $service);
    }

    private function finish(QuizAttempt $attempt, AttemptService $service, ?string $message = null): void
    {
        if ($attempt->isInProgress()) {
            $service->submit($attempt, timedOut: $attempt->isExpired());
        }

        session()->flash($message ? 'error' : 'success', $message ?? __('Test bol odovzdaný.'));

        $this->redirectRoute('attempts.show', $attempt);
    }

    private function attempt(): QuizAttempt
    {
        return QuizAttempt::where('user_id', auth()->id())->findOrFail($this->attemptId);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyResponse(QuizAnswer $answer): array
    {
        return match (QuestionType::from($answer->question_snapshot['type'])) {
            QuestionType::ShortAnswer => ['text' => ''],
            QuestionType::FillBlank => ['blanks' => []],
            QuestionType::Matching => ['pairs' => []],
            default => ['selected' => []],
        };
    }

    /**
     * Snapshot stripped of everything that would reveal the correct answer.
     *
     * @return array<string, mixed>
     */
    private function presentable(QuizAnswer $answer): array
    {
        $snapshot = $answer->question_snapshot;
        $type = QuestionType::from($snapshot['type']);
        $byId = collect($snapshot['options'])->keyBy('id');

        return [
            'question_id' => $answer->question_id,
            'type' => $type,
            'body' => $snapshot['body'],
            'has_image' => $snapshot['has_image'] ?? false,
            'points' => (float) $answer->max_points,
            'options' => $type->isChoice()
                ? collect($snapshot['options'])->map(fn (array $option): array => ['id' => $option['id'], 'body' => $option['body']])->all()
                : [],
            'left' => $type === QuestionType::Matching
                ? collect($snapshot['options'])->map(fn (array $option): array => ['id' => $option['id'], 'body' => $option['body']])->all()
                : [],
            'right' => $type === QuestionType::Matching
                ? collect($snapshot['right_order'] ?? [])->map(fn (int $id): array => ['id' => $id, 'body' => $byId[$id]['match_body'] ?? ''])->all()
                : [],
            // Text split into pieces around the [[n]] placeholders.
            'segments' => $type === QuestionType::FillBlank
                ? preg_split('/(\[\[\d{1,2}\]\])/', $snapshot['body'], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)
                : [],
        ];
    }

    public function render(): View
    {
        $attempt = $this->attempt()->load(['quiz', 'answers']);
        $answers = $attempt->answers->keyBy('question_id');
        $questions = collect($attempt->question_order)
            ->filter(fn (int $id): bool => $answers->has($id))
            ->map(fn (int $id): array => $this->presentable($answers[$id]))
            ->values();

        return view('livewire.student.quiz-player', [
            'attempt' => $attempt,
            'questions' => $questions,
            'question' => $questions[$this->current] ?? $questions->first(),
            'answeredCount' => collect($this->responses)->filter(fn (array $response): bool => $this->isAnswered($response))->count(),
            'secondsRemaining' => $attempt->secondsRemaining(),
        ])->title($attempt->quiz->title);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function isAnswered(array $response): bool
    {
        return collect($response)->flatten()->filter(fn ($value): bool => $value !== null && $value !== '' && $value !== false)->isNotEmpty();
    }
}
