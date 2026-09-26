<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\QuizStatus;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Quiz;
use App\Services\Analytics\AnalyticsFilter;
use App\Services\Analytics\QuizAnalytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, QuizAnalytics $analytics): View
    {
        $user = $request->user();
        $filter = new AnalyticsFilter($user);
        $classrooms = Classroom::whereHas('teachers', fn ($query) => $query->whereKey($user->id))->withCount('students')->orderBy('name')->get();

        return view('teacher.dashboard', [
            'coursesCount' => Course::manageableBy($user)->count(),
            'publishedCourses' => Course::manageableBy($user)->where('status', 'published')->count(),
            'classrooms' => $classrooms,
            'studentsCount' => $classrooms->sum('students_count'),
            'summary' => $analytics->summary($filter),
            'recentAttempts' => $analytics->attempts($filter)->with(['user:id,first_name,last_name', 'quiz:id,title'])->latest('finished_at')->limit(8)->get(),
            'struggling' => $analytics->studentStats($filter)->where('struggling', true)->take(5),
            'deadlines' => Quiz::manageableBy($user)
                ->where('status', QuizStatus::Published)
                ->whereBetween('due_at', [now(), now()->addWeeks(3)])
                ->with('course:id,title')
                ->orderBy('due_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
