<?php

namespace App\Http\Controllers;

use App\Enums\QuizStatus;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\Progress\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Course and chapter pages as a student sees them. Teachers use the same pages as a preview.
 */
class LearningController extends Controller
{
    public function __construct(private ProgressService $progress) {}

    public function course(Request $request, Course $course): View
    {
        Gate::authorize('view', $course);

        $user = $request->user();
        $isPreview = $user->can('update', $course);

        $course->load(['category', 'author', 'modules.chapters' => fn ($query) => $query
            ->when(! $isPreview, fn ($query) => $query->where('is_published', true)),
        ]);

        $completed = $this->progress->completedIds($user, $course);
        $unlocked = $course->modules->flatMap->chapters
            ->mapWithKeys(fn (Chapter $chapter): array => [$chapter->id => $this->progress->isUnlocked($user, $chapter->setRelation('course', $course))]);

        return view('learning.course', [
            'course' => $course,
            'isPreview' => $isPreview,
            'completed' => $completed,
            'unlocked' => $unlocked,
            'courseProgress' => CourseProgress::where('user_id', $user->id)->where('course_id', $course->id)->first(),
            'quizzes' => $this->quizzes($course, $isPreview),
            'bestResults' => $this->bestResults($course, $user),
            'announcements' => $course->announcements()->with('author:id,first_name,last_name')->latest()->limit(3)->get(),
        ]);
    }

    public function chapter(Request $request, Course $course, Chapter $chapter): View|RedirectResponse
    {
        Gate::authorize('view', $chapter);

        $user = $request->user();
        $isPreview = $user->can('update', $course);

        if (! $this->progress->isUnlocked($user, $chapter)) {
            return redirect()->route('courses.show', $course)->with('error', __('Najprv dokonči predchádzajúcu kapitolu.'));
        }

        if (! $isPreview) {
            $this->progress->markStarted($user, $chapter);
        }

        $chapters = $course->orderedChapters(publishedOnly: ! $isPreview);
        $index = $chapters->search(fn (Chapter $item): bool => $item->is($chapter));

        return view('learning.chapter', [
            'course' => $course,
            'chapter' => $chapter->load(['module', 'materials']),
            'previous' => $index > 0 ? $chapters[$index - 1] : null,
            'next' => $chapters[$index + 1] ?? null,
            'position' => $index + 1,
            'total' => $chapters->count(),
            'isPreview' => $isPreview,
            'isCompleted' => $this->progress->completedIds($user, $course)->contains($chapter->id),
            'quizzes' => $this->quizzes($course, $isPreview)->where('chapter_id', $chapter->id),
            'bestResults' => $this->bestResults($course, $user),
        ]);
    }

    /**
     * @return Collection<int, Quiz>
     */
    private function quizzes(Course $course, bool $isPreview): Collection
    {
        return $course->quizzes()
            ->when(! $isPreview, fn ($query) => $query->where('status', QuizStatus::Published))
            ->withCount('questions')
            ->orderBy('purpose')
            ->orderBy('title')
            ->get();
    }

    /**
     * Best finished attempt per quiz of this course for the user.
     *
     * @return Collection<int, QuizAttempt>
     */
    private function bestResults(Course $course, User $user): Collection
    {
        return QuizAttempt::where('user_id', $user->id)
            ->whereIn('quiz_id', $course->quizzes()->select('id'))
            ->whereNotNull('finished_at')
            ->with('quiz')
            ->get()
            ->groupBy('quiz_id')
            ->map(fn (Collection $attempts) => $attempts->sortByDesc('percentage')->first());
    }
}
