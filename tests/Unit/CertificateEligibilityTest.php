<?php

namespace Tests\Unit;

use App\Services\Certificates\CertificateEligibility;
use Tests\TestCase;

class CertificateEligibilityTest extends TestCase
{
    private CertificateEligibility $eligibility;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eligibility = new CertificateEligibility;
    }

    public function test_disabled_course_never_issues(): void
    {
        $result = $this->eligibility->evaluate(false, 0, [], [], [], []);

        $this->assertFalse($result->eligible);
    }

    public function test_all_conditions_met(): void
    {
        $result = $this->eligibility->evaluate(
            true, 70,
            [1 => 'Heslá', 2 => 'Phishing'], [1, 2],
            [10 => 'Záverečný test'], [10 => ['percentage' => 85.0, 'passed' => true]],
        );

        $this->assertTrue($result->eligible);
        $this->assertSame([], $result->missing);
        $this->assertSame(85.0, $result->finalPercentage);
    }

    public function test_missing_chapter_is_reported(): void
    {
        $result = $this->eligibility->evaluate(true, 0, [1 => 'Heslá', 2 => 'Phishing'], [1], [], []);

        $this->assertFalse($result->eligible);
        $this->assertCount(1, $result->missing);
        $this->assertStringContainsString('Phishing', $result->missing[0]);
        $this->assertNull($result->finalPercentage);
    }

    public function test_quiz_must_be_passed_and_average_must_reach_minimum(): void
    {
        $failed = $this->eligibility->evaluate(true, 70, [], [], [10 => 'Test A'], [10 => ['percentage' => 40.0, 'passed' => false]]);
        $this->assertFalse($failed->eligible);
        $this->assertCount(2, $failed->missing); // not passed + average below minimum

        $notTaken = $this->eligibility->evaluate(true, 70, [], [], [10 => 'Test A'], []);
        $this->assertFalse($notTaken->eligible);

        $lowAverage = $this->eligibility->evaluate(
            true, 80, [], [],
            [10 => 'A', 11 => 'B'],
            [10 => ['percentage' => 100.0, 'passed' => true], 11 => ['percentage' => 55.0, 'passed' => true]],
        );
        $this->assertFalse($lowAverage->eligible);
        $this->assertSame(77.5, $lowAverage->finalPercentage);
    }
}
