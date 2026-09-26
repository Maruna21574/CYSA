<?php

namespace Tests\Unit;

use App\Enums\QuestionType;
use App\Services\AI\AiQuestionParser;
use App\Services\Questions\QuestionValidator;
use Tests\TestCase;

class AiQuestionParserTest extends TestCase
{
    private AiQuestionParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new AiQuestionParser(new QuestionValidator);
    }

    /**
     * @param  list<array{0: string, 1: bool, 2?: string, 3?: int}>  $options
     * @return array<string, mixed>
     */
    private function question(string $type, string $body, array $options): array
    {
        return [
            'type' => $type,
            'body' => $body,
            'explanation' => 'Pretože.',
            'difficulty' => 'beginner',
            'topic' => 'phishing',
            'options' => array_map(fn (array $o): array => ['text' => $o[0], 'is_correct' => $o[1], 'match_text' => $o[2] ?? '', 'blank' => $o[3] ?? 0], $options),
        ];
    }

    public function test_valid_questions_of_all_types_are_accepted(): void
    {
        $data = ['questions' => [
            $this->question('single_choice', 'Čo je phishing?', [['Podvod', true], ['Hra', false], ['Vírus', false]]),
            $this->question('multiple_choice', 'Znaky phishingu?', [['Naliehavosť', true], ['Zlý odkaz', true], ['Podpis učiteľa', false]]),
            $this->question('true_false', 'Heslo zdieľam.', [['Pravda', false], ['Nepravda', true]]),
            $this->question('short_answer', 'Podvodný e-mail sa volá?', [['phishing', false]]),
            $this->question('fill_blank', 'Heslo má aspoň [[1]] znakov.', [['12', false, '', 1]]),
            $this->question('matching', 'Priraď', [['2FA', false, 'Druhý krok'], ['Malvér', false, 'Škodlivý softvér']]),
        ]];

        $result = $this->parser->parse($data, QuestionType::cases(), 10);

        $this->assertCount(6, $result['questions']);
        $this->assertSame([], $result['warnings']);
        // Text answers are always stored as accepted answers.
        $this->assertTrue($result['questions'][3]['options'][0]['is_correct']);
        $this->assertSame(1, $result['questions'][4]['options'][0]['blank_index']);
        $this->assertSame('Druhý krok', $result['questions'][5]['options'][0]['match_body']);
    }

    public function test_invalid_suggestions_are_dropped_with_warnings(): void
    {
        $data = ['questions' => [
            $this->question('single_choice', 'Dve správne?', [['A', true], ['B', true]]),
            $this->question('essay', 'Neznámy typ', [['A', true]]),
            $this->question('single_choice', '', [['A', true], ['B', false]]),
            'nie objekt',
            $this->question('single_choice', 'OK?', [['A', true], ['B', false]]),
        ]];

        $result = $this->parser->parse($data, QuestionType::cases(), 10);

        $this->assertCount(1, $result['questions']);
        $this->assertCount(4, $result['warnings']);
    }

    public function test_types_not_requested_by_teacher_are_rejected(): void
    {
        $data = ['questions' => [$this->question('true_false', 'Tvrdenie', [['Pravda', true], ['Nepravda', false]])]];

        $result = $this->parser->parse($data, [QuestionType::SingleChoice], 10);

        $this->assertSame([], $result['questions']);
    }

    public function test_markup_is_stripped_and_count_is_limited(): void
    {
        $data = ['questions' => array_fill(0, 5, $this->question('single_choice', '<script>alert(1)</script><b>Otázka?</b>', [['<i>A</i>', true], ['B', false]]))];

        $result = $this->parser->parse($data, QuestionType::cases(), 3);

        $this->assertCount(3, $result['questions']);
        $this->assertSame('alert(1)Otázka?', $result['questions'][0]['body']);
        $this->assertSame('A', $result['questions'][0]['options'][0]['body']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_garbage_answer_yields_nothing(): void
    {
        $this->assertSame([], $this->parser->parse(['foo' => 'bar'], QuestionType::cases(), 5)['questions']);
    }
}
