<?php

namespace App\Services\Questions;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;

/**
 * Persists a question together with its options, topics and tags.
 */
class QuestionService
{
    /**
     * Options are updated in place by ID (not recreated), so statistics of already given
     * answers keep pointing at the same option rows.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<array{id?: int|null, body: string, match_body?: string|null, blank_index?: int|null, is_correct?: bool}>  $options
     * @param  list<int>  $topicIds
     */
    public function save(Question $question, array $attributes, array $options, array $topicIds, string $tags): Question
    {
        return DB::transaction(function () use ($question, $attributes, $options, $topicIds, $tags): Question {
            $question->fill($attributes)->save();

            $this->syncOptions($question, $options);
            $question->topics()->sync($topicIds);
            $question->tags()->sync(Tag::idsFromInput($tags, $question->school_id));

            return $question;
        });
    }

    public function duplicate(Question $question): Question
    {
        return DB::transaction(function () use ($question): Question {
            $copy = $question->replicate(['image_path']);
            $copy->save();

            foreach ($question->options as $option) {
                $copy->options()->create($option->only(['body', 'match_body', 'blank_index', 'is_correct', 'position']));
            }

            $copy->topics()->sync($question->topics()->pluck('topics.id'));
            $copy->tags()->sync($question->tags()->pluck('tags.id'));

            return $copy;
        });
    }

    /**
     * Default options offered by the editor when the type changes.
     *
     * @return list<array{id: null, body: string, match_body: string, blank_index: int|null, is_correct: bool}>
     */
    public static function defaultOptions(QuestionType $type): array
    {
        $row = fn (string $body = '', bool $correct = false, ?int $blank = null): array => [
            'id' => null, 'body' => $body, 'match_body' => '', 'blank_index' => $blank, 'is_correct' => $correct,
        ];

        return match ($type) {
            QuestionType::TrueFalse => [$row(__('Pravda'), true), $row(__('Nepravda'))],
            QuestionType::ShortAnswer => [$row('', true)],
            QuestionType::FillBlank => [$row('', true, 1)],
            QuestionType::Matching => [$row(), $row(), $row()],
            default => [$row(), $row(), $row(), $row()],
        };
    }

    /**
     * @param  list<array<string, mixed>>  $options
     */
    private function syncOptions(Question $question, array $options): void
    {
        $keep = [];

        foreach (array_values($options) as $position => $option) {
            if (trim((string) ($option['body'] ?? '')) === '') {
                continue;
            }

            $attributes = [
                'body' => trim($option['body']),
                'match_body' => $question->type === QuestionType::Matching ? trim((string) ($option['match_body'] ?? '')) : null,
                'blank_index' => $question->type === QuestionType::FillBlank ? (int) $option['blank_index'] : null,
                // Accepted answers of text questions are all "correct".
                'is_correct' => in_array($question->type, [QuestionType::ShortAnswer, QuestionType::FillBlank, QuestionType::Matching], true)
                    || (bool) ($option['is_correct'] ?? false),
                'position' => $position,
            ];

            $existing = isset($option['id']) ? $question->options()->whereKey($option['id'])->first() : null;

            $keep[] = $existing
                ? tap($existing)->update($attributes)->id
                : $question->options()->create($attributes)->id;
        }

        $question->options()->whereNotIn('id', $keep)->delete();
    }
}
