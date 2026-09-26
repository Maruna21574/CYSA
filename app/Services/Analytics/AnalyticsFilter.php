<?php

namespace App\Services\Analytics;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Filter of the analytics pages. Built only from IDs the user is allowed to see,
 * so a forged query string cannot widen the data scope.
 */
final readonly class AnalyticsFilter
{
    public function __construct(
        public User $user,
        public ?int $classroomId = null,
        public ?int $courseId = null,
        public ?int $quizId = null,
        public ?Carbon $from = null,
        public ?Carbon $to = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $user = $request->user();

        $classroomId = $request->integer('classroom') ?: null;
        $courseId = $request->integer('course') ?: null;
        $quizId = $request->integer('quiz') ?: null;

        return new self(
            user: $user,
            classroomId: $classroomId && Classroom::visibleTo($user)->whereKey($classroomId)->exists() ? $classroomId : null,
            courseId: $courseId && Course::manageableBy($user)->whereKey($courseId)->exists() ? $courseId : null,
            quizId: $quizId && Quiz::manageableBy($user)->whereKey($quizId)->exists() ? $quizId : null,
            from: self::date($request->query('from')),
            to: self::date($request->query('to'))?->endOfDay(),
        );
    }

    public function withQuiz(int $quizId): self
    {
        return new self($this->user, $this->classroomId, $this->courseId, $quizId, $this->from, $this->to);
    }

    /**
     * @return array<string, int|string>
     */
    public function toQuery(): array
    {
        return array_filter([
            'classroom' => $this->classroomId,
            'course' => $this->courseId,
            'quiz' => $this->quizId,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
        ]);
    }

    private static function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
