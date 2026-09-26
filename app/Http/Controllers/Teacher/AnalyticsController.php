<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Quiz;
use App\Services\Analytics\AnalyticsFilter;
use App\Services\Analytics\QuizAnalytics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(Request $request, QuizAnalytics $analytics): View
    {
        abort_unless($request->user()->canTeach(), 403);

        $filter = AnalyticsFilter::fromRequest($request);
        $user = $request->user();

        return view('teacher.analytics.index', [
            'filter' => $filter,
            'summary' => $analytics->summary($filter),
            'hardest' => $analytics->questionStats($filter, 'asc', 5),
            'easiest' => $analytics->questionStats($filter, 'desc', 5),
            'wrongAnswers' => $analytics->commonWrongAnswers($filter, 5),
            'topics' => $analytics->topicStats($filter),
            'students' => $analytics->studentStats($filter),
            'progress' => CourseProgress::query()
                ->whereIn('course_id', Course::manageableBy($user)->when($filter->courseId, fn ($q, $id) => $q->whereKey($id))->select('id'))
                ->when($filter->classroomId, fn ($q, $id) => $q->whereIn('user_id', Classroom::find($id)->students()->select('users.id')))
                ->with(['user:id,first_name,last_name', 'course:id,title'])
                ->orderBy('percentage')
                ->limit(50)
                ->get(),
            'classrooms' => Classroom::visibleTo($user)
                ->when($user->role === UserRole::Teacher, fn ($q) => $q->whereHas('teachers', fn ($q) => $q->whereKey($user->id)))
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all(),
            'courses' => Course::manageableBy($user)->orderBy('title')->pluck('title', 'id')->all(),
            'quizzes' => Quiz::manageableBy($user)->orderBy('title')->pluck('title', 'id')->all(),
        ]);
    }
}
