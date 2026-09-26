<?php

namespace App\Jobs;

use App\Enums\AiGenerationStatus;
use App\Enums\QuestionSource;
use App\Enums\QuestionStatus;
use App\Enums\QuestionType;
use App\Models\AiGeneration;
use App\Models\Question;
use App\Models\Topic;
use App\Notifications\AiQuestionsReadyNotification;
use App\Services\AI\AiException;
use App\Services\AI\AIQuizGenerationService;
use App\Services\Files\TextExtraction\TextExtractor;
use App\Services\Questions\QuestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Generates question suggestions in the background (an AI request can take a minute).
 * Results are stored as DRAFT questions - they cannot be used in a quiz until a teacher
 * reviews and approves them.
 */
class GenerateAiQuestions implements ShouldQueue
{
    use Queueable;

    /** Never retried automatically: every attempt costs money and would duplicate drafts. */
    public int $tries = 1;

    public int $timeout = 360;

    public function __construct(public int $generationId) {}

    public function handle(AIQuizGenerationService $ai, TextExtractor $extractor, QuestionService $questions): void
    {
        $generation = AiGeneration::with(['material', 'chapter', 'user'])->find($this->generationId);

        if ($generation === null || $generation->status !== AiGenerationStatus::Pending) {
            return;
        }

        $generation->forceFill(['status' => AiGenerationStatus::Processing])->save();

        try {
            $text = $generation->material
                ? $extractor->textOf($generation->material)
                : trim(html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</li>', '</h1>', '</h2>'], "\n", (string) $generation->chapter?->content))));

            $result = $ai->generateQuestions(
                $text,
                $generation->requested_count,
                array_map(fn (string $type): QuestionType => QuestionType::from($type), $generation->question_types),
                $generation->difficulty,
            );
        } catch (AiException $e) {
            $this->markFailed($generation, $e->getMessage());

            return;
        }

        $topics = Topic::pluck('id', 'slug');

        DB::transaction(function () use ($generation, $result, $questions, $topics, $text): void {
            foreach ($result['questions'] as $suggestion) {
                $question = new Question;
                $question->school_id = $generation->course->school_id;
                $question->author_id = $generation->user_id;
                $question->status = QuestionStatus::Draft;
                $question->source = QuestionSource::Ai;
                $question->ai_generation_id = $generation->id;

                $questions->save($question, [
                    'course_id' => $generation->course_id,
                    'chapter_id' => $generation->chapter_id ?? $generation->material?->chapter_id,
                    'type' => $suggestion['type'],
                    'body' => $suggestion['body'],
                    'explanation' => $suggestion['explanation'],
                    'difficulty' => $suggestion['difficulty'],
                    'default_points' => 1,
                    'settings' => in_array($suggestion['type'], [QuestionType::MultipleChoice, QuestionType::FillBlank, QuestionType::Matching], true)
                        ? ['partial_credit' => true]
                        : [],
                ], $suggestion['options'], isset($topics[$suggestion['topic']]) ? [$topics[$suggestion['topic']]] : [], 'AI');
            }

            $generation->forceFill([
                'status' => AiGenerationStatus::Completed,
                'input_chars' => mb_strlen($text),
                'input_tokens' => $result['input_tokens'],
                'output_tokens' => $result['output_tokens'],
                'created_questions' => count($result['questions']),
                'warnings' => $result['warnings'],
            ])->save();
        });

        $generation->user?->notify(new AiQuestionsReadyNotification($generation));
    }

    public function failed(?Throwable $exception): void
    {
        $generation = AiGeneration::find($this->generationId);

        if ($generation && ! $generation->status->isFinished()) {
            $this->markFailed($generation, __('Generovanie sa nepodarilo dokončiť. Skúste to znova.'));
        }
    }

    private function markFailed(AiGeneration $generation, string $message): void
    {
        $generation->forceFill(['status' => AiGenerationStatus::Failed, 'error' => $message])->save();
        $generation->user?->notify(new AiQuestionsReadyNotification($generation));
    }
}
