<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Course and chapter pages as a student sees them. Teachers use the same pages as a preview.
 */
class LearningController extends Controller
{
    public function course(Request $request, Course $course): View
    {
        Gate::authorize('view', $course);

        $isPreview = $request->user()->can('update', $course);

        $course->load(['category', 'author', 'modules.chapters' => fn ($query) => $query
            ->when(! $isPreview, fn ($query) => $query->where('is_published', true)),
        ]);

        return view('learning.course', ['course' => $course, 'isPreview' => $isPreview]);
    }

    public function chapter(Request $request, Course $course, Chapter $chapter): View
    {
        Gate::authorize('view', $chapter);

        $isPreview = $request->user()->can('update', $course);
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
        ]);
    }
}
