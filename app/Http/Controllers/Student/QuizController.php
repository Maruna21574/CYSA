<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\Quiz\AttemptService;
use App\Services\Quiz\QuizUnavailableException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(private AttemptService $attempts) {}

    /**
     * Quiz introduction: rules, previous attempts and the start button.
     */
    public function show(Request $request, Quiz $quiz): View
    {
        Gate::authorize('view', $quiz);

        $quiz->load(['course', 'chapter'])->loadCount('questions');

        return view('student.quizzes.show', [
            'quiz' => $quiz,
            'availability' => $this->attempts->availability($quiz, $request->user()),
            'history' => QuizAttempt::where('quiz_id', $quiz->id)
                ->where('user_id', $request->user()->id)
                ->with('quiz')
                ->orderByDesc('attempt_number')
                ->get(),
        ]);
    }

    public function start(Request $request, Quiz $quiz): RedirectResponse
    {
        Gate::authorize('view', $quiz);

        try {
            $attempt = $this->attempts->start($quiz, $request->user());
        } catch (QuizUnavailableException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->route('student.attempts.play', $attempt);
    }
}
