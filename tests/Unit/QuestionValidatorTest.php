<?php

namespace Tests\Unit;

use App\Enums\QuestionType;
use App\Services\Questions\QuestionValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionValidatorTest extends TestCase
{
    /**
     * @return array<string, array{QuestionType, string, list<array<string, mixed>>, string|null}>
     */
    public static function questionProvider(): array
    {
        $opt = fn (string $body, bool $correct = false, ?string $match = null, ?int $blank = null): array => [
            'body' => $body, 'is_correct' => $correct, 'match_body' => $match, 'blank_index' => $blank,
        ];

        return [
            'single ok' => [QuestionType::SingleChoice, 'Q?', [$opt('A', true), $opt('B')], null],
            'single no correct' => [QuestionType::SingleChoice, 'Q?', [$opt('A'), $opt('B')], 'options'],
            'single two correct' => [QuestionType::SingleChoice, 'Q?', [$opt('A', true), $opt('B', true)], 'options'],
            'single one option' => [QuestionType::SingleChoice, 'Q?', [$opt('A', true), $opt('')], 'options'],
            'multiple ok' => [QuestionType::MultipleChoice, 'Q?', [$opt('A', true), $opt('B', true), $opt('C')], null],
            'multiple none correct' => [QuestionType::MultipleChoice, 'Q?', [$opt('A'), $opt('B')], 'options'],
            'true false ok' => [QuestionType::TrueFalse, 'Q', [$opt('Pravda'), $opt('Nepravda', true)], null],
            'short ok' => [QuestionType::ShortAnswer, 'Q?', [$opt('phishing', true)], null],
            'short empty' => [QuestionType::ShortAnswer, 'Q?', [$opt('')], 'options'],
            'blank ok' => [QuestionType::FillBlank, 'Heslo má [[1]] znakov a je [[2]].', [$opt('12', true, null, 1), $opt('unikátne', true, null, 2)], null],
            'blank no placeholder' => [QuestionType::FillBlank, 'Bez medzery', [$opt('x', true, null, 1)], 'body'],
            'blank gap in numbering' => [QuestionType::FillBlank, 'A [[1]] B [[3]]', [$opt('x', true, null, 1), $opt('y', true, null, 3)], 'body'],
            'blank missing answer' => [QuestionType::FillBlank, 'A [[1]] B [[2]]', [$opt('x', true, null, 1)], 'options'],
            'matching ok' => [QuestionType::Matching, 'Priraď', [$opt('A', false, '1'), $opt('B', false, '2')], null],
            'matching missing right' => [QuestionType::Matching, 'Priraď', [$opt('A', false, '1'), $opt('B', false, '')], 'options'],
            'matching duplicate right' => [QuestionType::Matching, 'Priraď', [$opt('A', false, 'x'), $opt('B', false, 'X')], 'options'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $options
     */
    #[DataProvider('questionProvider')]
    public function test_structure_rules(QuestionType $type, string $body, array $options, ?string $expectedErrorField): void
    {
        $errors = (new QuestionValidator)->validate($type, $body, $options);

        if ($expectedErrorField === null) {
            $this->assertSame([], $errors);
        } else {
            $this->assertArrayHasKey($expectedErrorField, $errors);
        }
    }
}
