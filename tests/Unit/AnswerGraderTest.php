<?php

namespace Tests\Unit;

use App\Services\Quiz\AnswerGrader;
use PHPUnit\Framework\TestCase;

class AnswerGraderTest extends TestCase
{
    private AnswerGrader $grader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grader = new AnswerGrader;
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function snapshot(string $type, array $options, array $settings = []): array
    {
        return ['type' => $type, 'options' => $options, 'settings' => $settings];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function choices(int ...$correct): array
    {
        return array_map(fn (int $id): array => ['id' => $id, 'body' => "O$id", 'is_correct' => in_array($id, $correct, true)], [1, 2, 3, 4]);
    }

    public function test_single_choice(): void
    {
        $snapshot = $this->snapshot('single_choice', $this->choices(2));

        $this->assertSame(1.0, $this->grader->grade($snapshot, ['selected' => [2]]));
        $this->assertSame(0.0, $this->grader->grade($snapshot, ['selected' => [1]]));
        $this->assertSame(0.0, $this->grader->grade($snapshot, ['selected' => [2, 3]]));
        $this->assertSame(0.0, $this->grader->grade($snapshot, null));
    }

    public function test_multiple_choice_all_or_nothing(): void
    {
        $snapshot = $this->snapshot('multiple_choice', $this->choices(1, 2));

        $this->assertSame(1.0, $this->grader->grade($snapshot, ['selected' => [2, 1]]));
        $this->assertSame(0.0, $this->grader->grade($snapshot, ['selected' => [1]]));
        $this->assertSame(0.0, $this->grader->grade($snapshot, ['selected' => [1, 2, 3]]));
    }

    public function test_multiple_choice_partial_credit_penalizes_wrong_picks(): void
    {
        $snapshot = $this->snapshot('multiple_choice', $this->choices(1, 2), ['partial_credit' => true]);

        $this->assertSame(0.5, $this->grader->grade($snapshot, ['selected' => [1]]));
        $this->assertSame(0.5, $this->grader->grade($snapshot, ['selected' => [1, 2, 3]]));
        // Ticking everything must not pay off.
        $this->assertSame(0.0, $this->grader->grade($snapshot, ['selected' => [1, 2, 3, 4]]));
        $this->assertSame(0.0, $this->grader->grade($snapshot, ['selected' => [3]]));
    }

    public function test_short_answer_is_case_and_whitespace_insensitive_by_default(): void
    {
        $snapshot = $this->snapshot('short_answer', [['id' => 1, 'body' => 'phishing'], ['id' => 2, 'body' => 'fišing']]);

        $this->assertSame(1.0, $this->grader->grade($snapshot, ['text' => '  Phishing. ']));
        $this->assertSame(1.0, $this->grader->grade($snapshot, ['text' => 'FIŠING']));
        $this->assertSame(0.0, $this->grader->grade($snapshot, ['text' => 'fishing']));
        $this->assertSame(0.0, $this->grader->grade($snapshot, ['text' => '']));
    }

    public function test_short_answer_case_sensitive(): void
    {
        $snapshot = $this->snapshot('short_answer', [['id' => 1, 'body' => 'GDPR']], ['case_sensitive' => true]);

        $this->assertSame(1.0, $this->grader->grade($snapshot, ['text' => 'GDPR']));
        $this->assertSame(0.0, $this->grader->grade($snapshot, ['text' => 'gdpr']));
    }

    public function test_fill_blank_with_and_without_partial_credit(): void
    {
        $options = [
            ['id' => 1, 'body' => '12', 'blank_index' => 1],
            ['id' => 2, 'body' => 'dvanásť', 'blank_index' => 1],
            ['id' => 3, 'body' => 'jedinečné', 'blank_index' => 2],
        ];

        $strict = $this->snapshot('fill_blank', $options);
        $partial = $this->snapshot('fill_blank', $options, ['partial_credit' => true]);

        $this->assertSame(1.0, $this->grader->grade($strict, ['blanks' => [1 => 'Dvanásť', 2 => 'jedinečné']]));
        $this->assertSame(0.0, $this->grader->grade($strict, ['blanks' => [1 => '12', 2 => 'rovnaké']]));
        $this->assertSame(0.5, $this->grader->grade($partial, ['blanks' => [1 => '12', 2 => 'rovnaké']]));
    }

    public function test_matching(): void
    {
        $options = [
            ['id' => 10, 'body' => 'A', 'match_body' => '1'],
            ['id' => 11, 'body' => 'B', 'match_body' => '2'],
            ['id' => 12, 'body' => 'C', 'match_body' => '3'],
            ['id' => 13, 'body' => 'D', 'match_body' => '4'],
        ];

        $this->assertSame(1.0, $this->grader->grade($this->snapshot('matching', $options), ['pairs' => [10 => 10, 11 => 11, 12 => 12, 13 => 13]]));
        $this->assertSame(0.0, $this->grader->grade($this->snapshot('matching', $options), ['pairs' => [10 => 11, 11 => 10, 12 => 12, 13 => 13]]));
        $this->assertSame(0.5, $this->grader->grade($this->snapshot('matching', $options, ['partial_credit' => true]), ['pairs' => [10 => 11, 11 => 10, 12 => 12, 13 => 13]]));
    }

    public function test_normalize_drops_unknown_options_and_limits_single_choice(): void
    {
        $snapshot = $this->snapshot('single_choice', $this->choices(2));

        $this->assertSame(['selected' => [2]], $this->grader->normalize($snapshot, ['selected' => ['999', '2', '3']]));
        $this->assertNull($this->grader->normalize($snapshot, ['selected' => ['999']]));
        $this->assertNull($this->grader->normalize($snapshot, 'garbage'));
    }

    public function test_normalize_matching_accepts_only_known_ids(): void
    {
        $snapshot = $this->snapshot('matching', [['id' => 1, 'body' => 'A', 'match_body' => 'x'], ['id' => 2, 'body' => 'B', 'match_body' => 'y']]);

        $this->assertSame(['pairs' => [1 => 2]], $this->grader->normalize($snapshot, ['pairs' => ['1' => '2', '5' => '1', '2' => '']]));
    }

    public function test_normalize_trims_and_limits_text(): void
    {
        $snapshot = $this->snapshot('short_answer', [['id' => 1, 'body' => 'x']]);

        $normalized = $this->grader->normalize($snapshot, ['text' => '  '.str_repeat('a', 900).'  ']);

        $this->assertSame(AnswerGrader::MAX_TEXT_LENGTH, mb_strlen($normalized['text']));
        $this->assertNull($this->grader->normalize($snapshot, ['text' => '   ']));
    }
}
