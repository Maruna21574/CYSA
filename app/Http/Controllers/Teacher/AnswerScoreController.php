<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Services\Audit\AuditLogger;
use App\Services\Quiz\AttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Teacher's manual correction of the points of one answer. Always audited.
 */
class AnswerScoreController extends Controller
{
    public function __invoke(Request $request, QuizAttempt $attempt, QuizAnswer $answer, AttemptService $attempts, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('viewResults', $attempt->quiz);
        abort_if($attempt->isInProgress(), 409);

        $points = (float) $request->validate([
            'points' => ['required', 'numeric', 'min:0', 'max:'.(float) $answer->max_points],
        ])['points'];

        $old = ['points' => (float) $answer->points_awarded, 'attempt_percentage' => (float) $attempt->percentage];
        $attempt = $attempts->overridePoints($answer, $points);

        $audit->log(AuditAction::AttemptScoreChanged, $attempt, $old, [
            'question_id' => $answer->question_id,
            'points' => $points,
            'attempt_percentage' => (float) $attempt->percentage,
        ]);

        return back()->with('success', __('Body boli upravené.'));
    }
}
