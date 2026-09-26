<?php

namespace App\Services\Quiz;

use App\Enums\QuestionType;
use Illuminate\Support\Str;

/**
 * Grades one answer against the frozen question snapshot. Pure logic without database access.
 *
 * Snapshot: ['type', 'options' => [['id', 'body', 'match_body', 'blank_index', 'is_correct'], ...], 'settings' => [...]]
 * Responses:
 *  - choice types:   ['selected' => [optionId, ...]]
 *  - short answer:   ['text' => '...']
 *  - fill the blank: ['blanks' => [blankIndex => '...']]
 *  - matching:       ['pairs' => [leftOptionId => rightOptionId]]
 */
class AnswerGrader
{
    public const MAX_TEXT_LENGTH = 500;

    /**
     * Share of the points the answer earns, from 0.0 to 1.0.
     *
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>|null  $response
     */
    public function grade(array $snapshot, ?array $response): float
    {
        if ($response === null || $response === []) {
            return 0.0;
        }

        $partial = (bool) ($snapshot['settings']['partial_credit'] ?? false);

        return match (QuestionType::from($snapshot['type'])) {
            QuestionType::SingleChoice, QuestionType::TrueFalse => $this->gradeSingle($snapshot, $response),
            QuestionType::MultipleChoice => $this->gradeMultiple($snapshot, $response, $partial),
            QuestionType::ShortAnswer => $this->gradeShort($snapshot, $response),
            QuestionType::FillBlank => $this->gradeBlanks($snapshot, $response, $partial),
            QuestionType::Matching => $this->gradeMatching($snapshot, $response, $partial),
        };
    }

    /**
     * Reduces browser input to a valid response for the snapshot: unknown option IDs are dropped,
     * texts are trimmed and shortened. Returns null when nothing was answered.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>|null
     */
    public function normalize(array $snapshot, mixed $input): ?array
    {
        if (! is_array($input)) {
            return null;
        }

        $optionIds = array_map('intval', array_column($snapshot['options'], 'id'));
        $type = QuestionType::from($snapshot['type']);

        $response = match ($type) {
            QuestionType::SingleChoice, QuestionType::TrueFalse, QuestionType::MultipleChoice => [
                'selected' => array_values(array_intersect(
                    $optionIds,
                    array_map('intval', array_filter((array) ($input['selected'] ?? []), 'is_numeric')),
                )),
            ],
            QuestionType::ShortAnswer => ['text' => $this->cleanText($input['text'] ?? '')],
            QuestionType::FillBlank => ['blanks' => collect((array) ($input['blanks'] ?? []))
                ->filter(fn ($value, $key): bool => is_numeric($key) && is_scalar($value))
                ->mapWithKeys(fn ($value, $key): array => [(int) $key => $this->cleanText($value)])
                ->filter(fn (string $value): bool => $value !== '')
                ->all()],
            QuestionType::Matching => ['pairs' => collect((array) ($input['pairs'] ?? []))
                ->filter(fn ($right, $left): bool => in_array((int) $left, $optionIds, true) && in_array((int) $right, $optionIds, true))
                ->mapWithKeys(fn ($right, $left): array => [(int) $left => (int) $right])
                ->all()],
        };

        if ($type === QuestionType::SingleChoice || $type === QuestionType::TrueFalse) {
            $response['selected'] = array_slice($response['selected'], 0, 1);
        }

        $isEmpty = match ($type) {
            QuestionType::ShortAnswer => $response['text'] === '',
            QuestionType::FillBlank => $response['blanks'] === [],
            QuestionType::Matching => $response['pairs'] === [],
            default => $response['selected'] === [],
        };

        return $isEmpty ? null : $response;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $response
     */
    private function gradeSingle(array $snapshot, array $response): float
    {
        $selected = $response['selected'] ?? [];

        return count($selected) === 1 && in_array((int) $selected[0], $this->correctIds($snapshot), true) ? 1.0 : 0.0;
    }

    /**
     * Partial credit: (correct picks - wrong picks) / all correct options, never below zero,
     * so ticking every box does not pay off.
     *
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $response
     */
    private function gradeMultiple(array $snapshot, array $response, bool $partial): float
    {
        $correct = $this->correctIds($snapshot);
        $selected = array_unique(array_map('intval', $response['selected'] ?? []));

        $hits = count(array_intersect($selected, $correct));
        $misses = count(array_diff($selected, $correct));

        if (! $partial) {
            return $hits === count($correct) && $misses === 0 ? 1.0 : 0.0;
        }

        return $correct === [] ? 0.0 : max(0.0, ($hits - $misses) / count($correct));
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $response
     */
    private function gradeShort(array $snapshot, array $response): float
    {
        $caseSensitive = (bool) ($snapshot['settings']['case_sensitive'] ?? false);
        $given = $this->normalizeText((string) ($response['text'] ?? ''), $caseSensitive);

        foreach ($snapshot['options'] as $option) {
            if ($given !== '' && $given === $this->normalizeText($option['body'], $caseSensitive)) {
                return 1.0;
            }
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $response
     */
    private function gradeBlanks(array $snapshot, array $response, bool $partial): float
    {
        $caseSensitive = (bool) ($snapshot['settings']['case_sensitive'] ?? false);
        $accepted = [];

        foreach ($snapshot['options'] as $option) {
            $accepted[(int) $option['blank_index']][] = $this->normalizeText($option['body'], $caseSensitive);
        }

        if ($accepted === []) {
            return 0.0;
        }

        $correct = 0;

        foreach ($accepted as $blank => $answers) {
            $given = $this->normalizeText((string) ($response['blanks'][$blank] ?? ''), $caseSensitive);

            if ($given !== '' && in_array($given, $answers, true)) {
                $correct++;
            }
        }

        return $this->share($correct, count($accepted), $partial);
    }

    /**
     * A pair is right when the student put the left item next to its own right counterpart.
     *
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $response
     */
    private function gradeMatching(array $snapshot, array $response, bool $partial): float
    {
        $pairs = $response['pairs'] ?? [];
        $correct = 0;

        foreach ($snapshot['options'] as $option) {
            if ((int) ($pairs[$option['id']] ?? 0) === (int) $option['id']) {
                $correct++;
            }
        }

        return $this->share($correct, count($snapshot['options']), $partial);
    }

    private function share(int $correct, int $total, bool $partial): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return $partial ? $correct / $total : ($correct === $total ? 1.0 : 0.0);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return list<int>
     */
    private function correctIds(array $snapshot): array
    {
        return array_values(array_map(
            fn (array $option): int => (int) $option['id'],
            array_filter($snapshot['options'], fn (array $option): bool => (bool) $option['is_correct']),
        ));
    }

    /**
     * Whitespace and a trailing full stop never decide about correctness.
     */
    private function normalizeText(string $text, bool $caseSensitive): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        $text = rtrim($text, '.');

        return $caseSensitive ? $text : mb_strtolower($text);
    }

    private function cleanText(mixed $value): string
    {
        return is_scalar($value) ? Str::limit(trim((string) $value), self::MAX_TEXT_LENGTH, '') : '';
    }
}
