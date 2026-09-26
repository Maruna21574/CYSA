<?php

namespace App\Services\Questions;

use App\Enums\QuestionType;
use App\Models\Question;

/**
 * Structural rules of a question per type (how many options, which ones are correct, blanks...).
 * Field-level rules (lengths, types) are handled by the regular validator.
 */
class QuestionValidator
{
    public const MAX_OPTIONS = 12;

    /**
     * @param  list<array{body?: string|null, match_body?: string|null, blank_index?: int|null, is_correct?: bool}>  $options
     * @return array<string, string> field => message
     */
    public function validate(QuestionType $type, string $body, array $options): array
    {
        $options = array_values(array_filter($options, fn (array $option): bool => trim((string) ($option['body'] ?? '')) !== ''));
        $correct = count(array_filter($options, fn (array $option): bool => (bool) ($option['is_correct'] ?? false)));

        if (count($options) > self::MAX_OPTIONS) {
            return ['options' => __('Otázka môže mať najviac :max možností.', ['max' => self::MAX_OPTIONS])];
        }

        return match ($type) {
            QuestionType::SingleChoice => match (true) {
                count($options) < 2 => ['options' => __('Zadajte aspoň dve možnosti.')],
                $correct !== 1 => ['options' => __('Označte práve jednu správnu možnosť.')],
                default => [],
            },
            QuestionType::MultipleChoice => match (true) {
                count($options) < 2 => ['options' => __('Zadajte aspoň dve možnosti.')],
                $correct < 1 => ['options' => __('Označte aspoň jednu správnu možnosť.')],
                default => [],
            },
            QuestionType::TrueFalse => count($options) === 2 && $correct === 1
                ? []
                : ['options' => __('Vyberte, či je tvrdenie pravdivé alebo nepravdivé.')],
            QuestionType::ShortAnswer => count($options) < 1
                ? ['options' => __('Zadajte aspoň jednu akceptovanú odpoveď.')]
                : [],
            QuestionType::FillBlank => $this->validateBlanks($body, $options),
            QuestionType::Matching => $this->validateMatching($options),
        };
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return array<string, string>
     */
    private function validateBlanks(string $body, array $options): array
    {
        $blanks = Question::blankIndexes($body);

        if ($blanks === []) {
            return ['body' => __('Označte v texte aspoň jednu medzeru, napríklad [[1]].')];
        }

        $sorted = $blanks;
        sort($sorted);

        if ($sorted !== range(1, count($blanks))) {
            return ['body' => __('Medzery číslujte postupne od 1: [[1]], [[2]], …')];
        }

        $answered = array_unique(array_map(fn (array $option): int => (int) ($option['blank_index'] ?? 0), $options));

        foreach ($blanks as $blank) {
            if (! in_array($blank, $answered, true)) {
                return ['options' => __('Zadajte odpoveď pre medzeru [[:n]].', ['n' => $blank])];
            }
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return array<string, string>
     */
    private function validateMatching(array $options): array
    {
        if (count($options) < 2) {
            return ['options' => __('Zadajte aspoň dve dvojice.')];
        }

        $right = array_map(fn (array $option): string => mb_strtolower(trim((string) ($option['match_body'] ?? ''))), $options);

        if (in_array('', $right, true)) {
            return ['options' => __('Každá dvojica musí mať vyplnené obe strany.')];
        }

        if (count(array_unique($right)) !== count($right)) {
            return ['options' => __('Položky vpravo sa nesmú opakovať.')];
        }

        return [];
    }
}
