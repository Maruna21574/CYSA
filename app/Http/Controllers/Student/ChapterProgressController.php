<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Course;
use App\Services\Progress\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChapterProgressController extends Controller
{
    /**
     * "Mark as completed" button of a chapter.
     */
    public function store(Request $request, Course $course, Chapter $chapter, ProgressService $progress): RedirectResponse
    {
        Gate::authorize('view', $chapter);
        abort_unless($progress->isUnlocked($request->user(), $chapter), 403);

        $progress->markCompleted($request->user(), $chapter);

        $chapters = $course->orderedChapters(publishedOnly: true);
        $index = $chapters->search(fn (Chapter $item): bool => $item->is($chapter));
        $next = $chapters[$index + 1] ?? null;

        return $next
            ? redirect()->route('chapters.show', [$course, $next])->with('success', __('Kapitola je dokončená. Pokračuj ďalšou.'))
            : redirect()->route('courses.show', $course)->with('success', __('Dokončil(a) si poslednú kapitolu kurzu!'));
    }
}
