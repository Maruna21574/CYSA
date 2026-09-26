<?php

namespace App\Http\Controllers\School;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\User;
use App\Services\Analytics\AnalyticsFilter;
use App\Services\Analytics\QuizAnalytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, QuizAnalytics $analytics): View
    {
        $user = $request->user();
        $school = $user->school;
        $filter = new AnalyticsFilter($user, from: now()->subDays(30));

        return view('school.dashboard', [
            'school' => $school,
            'teachers' => User::where('school_id', $school->id)->whereIn('role', [UserRole::Teacher, UserRole::SchoolAdmin])->count(),
            'students' => User::where('school_id', $school->id)->where('role', UserRole::Student)->count(),
            'activeStudents' => User::where('school_id', $school->id)->where('role', UserRole::Student)->where('last_login_at', '>=', now()->subDays(30))->count(),
            'classrooms' => Classroom::where('school_id', $school->id)->withCount('students')->orderByDesc('school_year')->orderBy('name')->limit(10)->get(),
            'courses' => Course::where('school_id', $school->id)->count(),
            'publishedCourses' => Course::where('school_id', $school->id)->where('status', 'published')->count(),
            'summary' => $analytics->summary($filter),
            'topics' => $analytics->topicStats(new AnalyticsFilter($user)),
        ]);
    }
}
