<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultController extends Controller
{
    /**
     * History of all the student's attempts.
     */
    public function index(Request $request): View
    {
        $attempts = QuizAttempt::where('user_id', $request->user()->id)
            ->with('quiz.course:id,title')
            ->latest('started_at')
            ->paginate(20);

        return view('student.results.index', ['attempts' => $attempts]);
    }
}
