<?php

namespace App\Services\Certificates;

use App\Enums\AttemptStatus;
use App\Enums\AuditAction;
use App\Enums\QuizPurpose;
use App\Enums\QuizStatus;
use App\Events\CertificateIssued;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Progress\ProgressService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Evaluates certificate conditions from the database and issues certificates automatically.
 */
class CertificateService
{
    /** Unambiguous characters only (no 0/O, 1/I/L) - codes are typed in by hand. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function __construct(
        private CertificateEligibility $eligibility,
        private ProgressService $progress,
        private AuditLogger $audit,
    ) {}

    public function evaluate(User $user, Course $course): EligibilityResult
    {
        [$chapters, $quizzes] = $this->requirements($course);

        $best = QuizAttempt::where('user_id', $user->id)
            ->where('status', AttemptStatus::Completed)
            ->whereIn('quiz_id', array_keys($quizzes))
            ->get(['quiz_id', 'percentage', 'passed'])
            ->groupBy('quiz_id')
            ->map(fn ($attempts): array => [
                'percentage' => (float) $attempts->max('percentage'),
                'passed' => $attempts->contains('passed', true),
            ])
            ->all();

        return $this->eligibility->evaluate(
            $course->certificate_enabled,
            $course->certificate_min_percentage,
            $chapters,
            $this->progress->completedIds($user, $course)->all(),
            $quizzes,
            $best,
        );
    }

    /**
     * Issues the certificate when the student is eligible and does not have one yet.
     */
    public function issueIfEligible(User $user, Course $course): ?Certificate
    {
        if (! $course->certificate_enabled || ! $user->canSignIn() || $this->existing($user, $course)) {
            return null;
        }

        $result = $this->evaluate($user, $course);

        if (! $result->eligible) {
            return null;
        }

        try {
            $certificate = DB::transaction(function () use ($user, $course, $result): Certificate {
                $certificate = new Certificate;
                $certificate->forceFill([
                    'code' => $this->uniqueCode(),
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'holder_name' => $user->name,
                    'course_title' => $course->title,
                    'school_name' => $course->school?->name,
                    'teacher_name' => $course->author?->name,
                    'final_percentage' => $result->finalPercentage,
                    'issued_at' => now(),
                ])->save();

                return $certificate;
            });
        } catch (UniqueConstraintViolationException) {
            // Issued concurrently by another request (two events at once) - nothing to do.
            return null;
        }

        $this->audit->log(AuditAction::CertificateIssued, $certificate, newValues: [
            'code' => $certificate->code,
            'course_id' => $course->id,
            'final_percentage' => $result->finalPercentage,
        ], user: $user);

        CertificateIssued::dispatch($certificate);

        return $certificate;
    }

    public function revoke(Certificate $certificate, string $reason): void
    {
        $certificate->forceFill(['revoked_at' => now(), 'revoked_reason' => $reason])->save();

        $this->audit->log(AuditAction::CertificateRevoked, $certificate, newValues: ['reason' => $reason]);
    }

    /**
     * Explicit requirements, or by default all published chapters and all published
     * graded quizzes / post-tests of the course.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    public function requirements(Course $course): array
    {
        $explicit = $course->certificateRequirements()->get();
        $publishedChapters = $course->chapters()->where('is_published', true);
        $publishedQuizzes = $course->quizzes()->where('status', QuizStatus::Published);

        $chapters = $explicit->whereNotNull('chapter_id')->isNotEmpty()
            ? $publishedChapters->whereIn('id', $explicit->pluck('chapter_id')->filter())
            : $publishedChapters;

        $quizzes = $explicit->whereNotNull('quiz_id')->isNotEmpty()
            ? $publishedQuizzes->whereIn('id', $explicit->pluck('quiz_id')->filter())
            : $publishedQuizzes->whereIn('purpose', [QuizPurpose::Graded, QuizPurpose::PostTest]);

        return [
            $chapters->pluck('title', 'id')->all(),
            $quizzes->pluck('title', 'id')->all(),
        ];
    }

    private function existing(User $user, Course $course): bool
    {
        return Certificate::where('user_id', $user->id)->where('course_id', $course->id)->exists();
    }

    private function uniqueCode(): string
    {
        do {
            $random = '';

            for ($i = 0; $i < 12; $i++) {
                $random .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }

            $code = 'CYSA-'.implode('-', str_split($random, 4));
        } while (Certificate::where('code', $code)->exists());

        return $code;
    }

    public static function normalizeCode(string $code): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9-]/', '', trim($code)));
    }
}
