<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ChapterRequest;
use App\Models\Chapter;
use App\Models\Course;
use App\Support\Positioning;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ChapterController extends Controller
{
    public function create(Request $request, Course $course): View
    {
        Gate::authorize('update', $course);

        return view('teacher.chapters.create', [
            'course' => $course,
            'chapter' => new Chapter(['is_published' => true, 'requires_previous' => $course->sequential_chapters]),
            'modules' => $course->modules()->pluck('title', 'id')->all(),
            'selectedModule' => $request->integer('module') ?: null,
        ]);
    }

    public function store(ChapterRequest $request, Course $course): RedirectResponse
    {
        $module = $course->modules()->findOrFail($request->integer('module_id'));

        $chapter = new Chapter($request->safe()->except('module_id'));
        $chapter->course_id = $course->id;
        $chapter->module_id = $module->id;
        $chapter->position = Positioning::next(Chapter::where('module_id', $module->id));
        $chapter->save();

        $course->touch();

        return redirect()->route('teacher.courses.chapters.edit', [$course, $chapter])
            ->with('success', __('Kapitola bola vytvorená. Teraz môžete pridať materiály.'));
    }

    public function edit(Course $course, Chapter $chapter): View
    {
        Gate::authorize('update', $chapter);

        return view('teacher.chapters.edit', [
            'course' => $course,
            'chapter' => $chapter,
            'modules' => $course->modules()->pluck('title', 'id')->all(),
            'selectedModule' => $chapter->module_id,
        ]);
    }

    public function update(ChapterRequest $request, Course $course, Chapter $chapter): RedirectResponse
    {
        $chapter->fill($request->safe()->except('module_id'));

        if ($chapter->module_id !== $request->integer('module_id')) {
            $chapter->module_id = $request->integer('module_id');
            $chapter->position = Positioning::next(Chapter::where('module_id', $chapter->module_id));
        }

        $chapter->save();
        $course->touch();

        return redirect()->route('teacher.courses.chapters.edit', [$course, $chapter])->with('success', __('Kapitola bola uložená.'));
    }

    public function destroy(Course $course, Chapter $chapter): RedirectResponse
    {
        Gate::authorize('delete', $chapter);

        $chapter->delete();
        $course->touch();

        return redirect()->route('teacher.courses.show', $course)->with('success', __('Kapitola bola odstránená.'));
    }
}
