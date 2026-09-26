<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AuditAction;
use App\Enums\QuestionStatus;
use App\Enums\QuizStatus;
use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\CourseNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class QuizStatusController extends Controller
{
    public function __invoke(Request $request, Quiz $quiz, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('update', $quiz);

        $status = QuizStatus::from($request->validate([
            'status' => ['required', Rule::enum(QuizStatus::class)],
        ])['status']);

        if ($status === $quiz->status) {
            return back();
        }

        if ($status === QuizStatus::Published) {
            if (! $quiz->questions()->exists()) {
                return back()->with('error', __('Test musí obsahovať aspoň jednu otázku.'));
            }

            // AI suggestions that the teacher has not approved yet must never reach students.
            if ($quiz->questions()->where('status', QuestionStatus::Draft)->exists()) {
                return back()->with('error', __('Test obsahuje neschválené otázky. Najprv ich skontrolujte.'));
            }
        }

        $old = $quiz->status;
        $firstPublish = $status === QuizStatus::Published && $quiz->published_at === null;

        $quiz->forceFill([
            'status' => $status,
            'published_at' => $status === QuizStatus::Published ? ($quiz->published_at ?? now()) : $quiz->published_at,
        ])->save();

        $audit->log(match ($status) {
            QuizStatus::Published => AuditAction::QuizPublished,
            QuizStatus::Archived => AuditAction::QuizArchived,
            QuizStatus::Draft => AuditAction::QuizUnpublished,
        }, $quiz, ['status' => $old->value], ['status' => $status->value]);

        if ($firstPublish) {
            app(CourseNotifier::class)->quizPublished($quiz);
        }

        return back()->with('success', match ($status) {
            QuizStatus::Published => __('Test bol publikovaný.'),
            QuizStatus::Archived => __('Test bol archivovaný.'),
            QuizStatus::Draft => __('Test bol vrátený do konceptu.'),
        });
    }
}
