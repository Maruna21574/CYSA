<?php

namespace App\Http\Controllers\Student;

use App\Enums\AttemptStatus;
use App\Gamification\GamificationService;
use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\Progress\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ProgressService $progress, GamificationService $gamification): View
    {
        $user = $request->user();
        $gamified = $gamification->enabledFor($user);
        $courses = Course::availableTo($user)->with('category:id,name')->orderBy('title')->get();
        $courseProgress = CourseProgress::where('user_id', $user->id)->get()->keyBy('course_id');

        // Next chapter to study in every unfinished course.
        $continue = $courses
            ->filter(fn (Course $course): bool => ($courseProgress[$course->id]->completed_at ?? null) === null)
            ->map(function (Course $course) use ($user, $progress): ?array {
                $done = $progress->completedIds($user, $course);
                $next = $course->orderedChapters(publishedOnly: true)->first(fn ($chapter): bool => ! $done->contains($chapter->id));

                return $next ? ['course' => $course, 'chapter' => $next] : null;
            })
            ->filter()
            ->take(4);

        $finishedQuizIds = QuizAttempt::where('user_id', $user->id)->where('status', AttemptStatus::Completed)->pluck('quiz_id');

        return view('student.dashboard', [
            'courses' => $courses,
            'courseProgress' => $courseProgress,
            'continue' => $continue,
            'upcoming' => Quiz::availableTo($user)
                ->whereNotIn('id', $finishedQuizIds)
                ->where(fn ($query) => $query->whereNull('due_at')->orWhere('due_at', '>', now()))
                ->with('course:id,title')
                ->orderByRaw('due_at IS NULL')
                ->orderBy('due_at')
                ->limit(5)
                ->get(),
            'gamification' => $gamified ? [
                'stats' => $gamification->stats($user),
                'badges' => Badge::whereIn('id', DB::table('user_badges')->where('user_id', $user->id)->select('badge_id'))->get(),
            ] : null,
            'recent' => QuizAttempt::where('user_id', $user->id)
                ->where('status', AttemptStatus::Completed)
                ->with('quiz')
                ->latest('finished_at')
                ->limit(5)
                ->get(),
        ]);
    }
}
