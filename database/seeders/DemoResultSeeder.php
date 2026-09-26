<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Enums\QuizPurpose;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Progress\ProgressService;
use App\Services\Quiz\AttemptService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Realistic demo results: every student writes the pre-test, studies the chapters and most of
 * them write the post-test with a better score - data for the teacher analytics and the
 * research export.
 */
class DemoResultSeeder extends Seeder
{
    /** Probability of a correct answer before / after the course, per demo student. */
    private const SKILL = [
        'student1@cysa.test' => [0.45, 0.90],
        'student2@cysa.test' => [0.30, 0.75],
        'student3@cysa.test' => [0.60, 0.95],
        'student4@cysa.test' => [0.25, 0.55],
        'student5@cysa.test' => [0.50, 0.80],
        'student6@cysa.test' => [0.35, null], // has not finished the course yet
    ];

    public function run(AttemptService $attempts, ProgressService $progress): void
    {
        $course = Course::where('slug', 'zaklady-kybernetickej-bezpecnosti')->firstOrFail();

        if ($course->quizzes()->whereHas('questions')->doesntExist() || QuizAttempt::exists()) {
            return;
        }

        mt_srand(2026);

        $quizzes = $course->quizzes()->get()->keyBy(fn ($quiz) => $quiz->purpose->value.'|'.$quiz->title);
        $pre = $quizzes->first(fn ($quiz) => $quiz->purpose === QuizPurpose::PreTest);
        $post = $quizzes->first(fn ($quiz) => $quiz->purpose === QuizPurpose::PostTest);
        $chapterQuizzes = $quizzes->filter(fn ($quiz) => $quiz->chapter_id !== null);
        $chapters = $course->orderedChapters(publishedOnly: true);

        foreach (self::SKILL as $email => [$before, $after]) {
            $student = User::where('email', $email)->where('role', UserRole::Student)->first();

            if (! $student) {
                continue;
            }

            Carbon::setTestNow(now()->subDays(21)->setTime(9, mt_rand(0, 50)));
            $this->write($attempts, $pre, $student, $before);

            $chaptersToFinish = $after === null ? 2 : $chapters->count();

            foreach ($chapters->take($chaptersToFinish) as $index => $chapter) {
                Carbon::setTestNow(now()->addDays(2)->addMinutes(mt_rand(5, 90)));
                $progress->markStarted($student, $chapter);
                $progress->markCompleted($student, $chapter);

                foreach ($chapterQuizzes->where('chapter_id', $chapter->id) as $quiz) {
                    $this->write($attempts, $quiz, $student, min(1, ($before + ($after ?? $before)) / 2 + $index * 0.05));
                }
            }

            if ($after !== null) {
                Carbon::setTestNow(now()->addDays(3));
                $this->write($attempts, $post, $student, $after);
            }
        }

        Carbon::setTestNow();
    }

    private function write(AttemptService $attempts, $quiz, User $student, float $skill): void
    {
        $attempt = $attempts->start($quiz, $student);

        foreach ($attempt->answers as $answer) {
            $correct = mt_rand(1, 100) <= (int) round($skill * 100);
            $attempts->saveAnswer($attempt, $answer->question_id, $this->response($answer, $correct));
        }

        Carbon::setTestNow(now()->addMinutes(mt_rand(4, 14))->addSeconds(mt_rand(0, 59)));
        $attempts->submit($attempt);
    }

    /**
     * @return array<string, mixed>
     */
    private function response(QuizAnswer $answer, bool $correct): array
    {
        $snapshot = $answer->question_snapshot;
        $options = collect($snapshot['options']);

        return match (QuestionType::from($snapshot['type'])) {
            QuestionType::SingleChoice, QuestionType::TrueFalse => ['selected' => [
                ($correct ? $options->firstWhere('is_correct', true) : $options->where('is_correct', false)->random())['id'],
            ]],
            QuestionType::MultipleChoice => ['selected' => $correct
                ? $options->where('is_correct', true)->pluck('id')->all()
                : [$options->where('is_correct', false)->random()['id'], $options->firstWhere('is_correct', true)['id']]],
            QuestionType::ShortAnswer => ['text' => $correct ? $options->first()['body'] : 'neviem'],
            QuestionType::FillBlank => ['blanks' => $options->groupBy('blank_index')
                ->map(fn ($accepted) => $correct ? $accepted->first()['body'] : 'x')
                ->all()],
            QuestionType::Matching => ['pairs' => $correct
                ? $options->mapWithKeys(fn ($option) => [$option['id'] => $option['id']])->all()
                : $options->pluck('id')->combine($options->pluck('id')->reverse()->values())->all()],
        };
    }
}
