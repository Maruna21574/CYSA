<?php

namespace App\Services\AI;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Services\Questions\QuestionValidator;
use Illuminate\Support\Str;

/**
 * Turns the raw JSON answer of the model into questions the QuestionService can store.
 * Nothing from the model is trusted: types, lengths and structure are validated again with
 * the same rules as questions written by hand; invalid suggestions are dropped with a warning.
 */
class AiQuestionParser
{
    private const MAX_BODY = 5000;

    private const MAX_OPTION = 1000;

    public function __construct(private QuestionValidator $validator) {}

    /**
     * @param  array<string, mixed>  $data  decoded model answer: {"questions": [...]}
     * @param  list<QuestionType>  $allowedTypes
     * @return array{questions: list<array{type: QuestionType, body: string, explanation: string|null, difficulty: Difficulty|null, topic: string|null, options: list<array{body: string, match_body: string, blank_index: int|null, is_correct: bool}>}>, warnings: list<string>}
     */
    public function parse(array $data, array $allowedTypes, int $limit): array
    {
        $questions = [];
        $warnings = [];

        foreach (array_values(is_array($data['questions'] ?? null) ? $data['questions'] : []) as $index => $raw) {
            $number = $index + 1;

            if (count($questions) >= $limit) {
                $warnings[] = __('AI navrhla viac otázok, než ste žiadali – nadbytočné boli vynechané.');
                break;
            }

            if (! is_array($raw)) {
                $warnings[] = __('Návrh č. :n mal neplatný formát a bol vynechaný.', ['n' => $number]);

                continue;
            }

            $type = QuestionType::tryFrom((string) ($raw['type'] ?? ''));

            if ($type === null || ! in_array($type, $allowedTypes, true)) {
                $warnings[] = __('Návrh č. :n mal nepovolený typ otázky a bol vynechaný.', ['n' => $number]);

                continue;
            }

            $body = $this->clean($raw['body'] ?? '', self::MAX_BODY);
            $options = $this->options($type, is_array($raw['options'] ?? null) ? $raw['options'] : []);

            if ($body === '') {
                $warnings[] = __('Návrh č. :n nemal znenie otázky a bol vynechaný.', ['n' => $number]);

                continue;
            }

            $errors = $this->validator->validate($type, $body, $options);

            if ($errors !== []) {
                $warnings[] = __('Návrh č. :n nespĺňal pravidlá otázky (:reason) a bol vynechaný.', ['n' => $number, 'reason' => implode(' ', $errors)]);

                continue;
            }

            $questions[] = [
                'type' => $type,
                'body' => $body,
                'explanation' => $this->clean($raw['explanation'] ?? '', self::MAX_BODY) ?: null,
                'difficulty' => Difficulty::tryFrom((string) ($raw['difficulty'] ?? '')),
                'topic' => is_string($raw['topic'] ?? null) ? $raw['topic'] : null,
                'options' => $options,
            ];
        }

        return ['questions' => $questions, 'warnings' => array_values(array_unique($warnings))];
    }

    /**
     * @param  array<int, mixed>  $rawOptions
     * @return list<array{body: string, match_body: string, blank_index: int|null, is_correct: bool}>
     */
    private function options(QuestionType $type, array $rawOptions): array
    {
        $options = [];

        foreach (array_slice($rawOptions, 0, QuestionValidator::MAX_OPTIONS) as $raw) {
            if (! is_array($raw)) {
                continue;
            }

            $options[] = [
                'body' => $this->clean($raw['text'] ?? '', self::MAX_OPTION),
                'match_body' => $type === QuestionType::Matching ? $this->clean($raw['match_text'] ?? '', self::MAX_OPTION) : '',
                'blank_index' => $type === QuestionType::FillBlank ? max(1, (int) ($raw['blank'] ?? 1)) : null,
                'is_correct' => (bool) ($raw['is_correct'] ?? false),
            ];
        }

        // Text answers are all "accepted answers", whatever the model marked.
        if (in_array($type, [QuestionType::ShortAnswer, QuestionType::FillBlank, QuestionType::Matching], true)) {
            $options = array_map(fn (array $option): array => [...$option, 'is_correct' => true], $options);
        }

        return $options;
    }

    /**
     * Plain text only: markup from the model is removed, whitespace normalized.
     */
    private function clean(mixed $value, int $max): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $text = strip_tags((string) $value);
        $text = trim(preg_replace('/[ \t]+/u', ' ', $text) ?? '');

        return Str::limit($text, $max, '');
    }
}
