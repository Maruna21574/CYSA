<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Services\Quiz\AttemptService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Result of one attempt. Students see only what the quiz settings allow; teachers see everything.
 */
class AttemptResultController extends Controller
{
    public function __invoke(Request $request, QuizAttempt $attempt, AttemptService $attempts): View|RedirectResponse
    {
        Gate::authorize('view', $attempt);

        $isOwner = $attempt->user_id === $request->user()->id;

        if ($attempt->isInProgress()) {
            if ($attempt->isExpired()) {
                $attempts->submit($attempt, timedOut: true);
                $attempt->refresh();
            } elseif ($isOwner) {
                return redirect()->route('student.attempts.play', $attempt);
            }
        }

        $attempt->load(['quiz.course', 'user', 'answers']);
        $order = array_flip($attempt->question_order);

        return view('attempts.result', [
            'attempt' => $attempt,
            'answers' => $attempt->answers->sortBy(fn ($answer) => $order[$answer->question_id] ?? PHP_INT_MAX)->values(),
            'showScore' => ! $isOwner || $attempt->scoreIsVisible(),
            'showCorrect' => ! $isOwner || $attempt->correctAnswersAreVisible(),
            'isOwner' => $isOwner,
        ]);
    }
}
