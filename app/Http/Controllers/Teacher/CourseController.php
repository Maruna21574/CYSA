<?php

namespace App\Http\Controllers\Teacher;

use App\Enums\AuditAction;
use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\CourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Services\Audit\AuditLogger;
use App\Services\Files\MaterialStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private AuditLogger $audit, private MaterialStorage $storage) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Course::class);

        $search = trim((string) $request->query('q'));
        $status = CourseStatus::tryFrom((string) $request->query('status'));

        $courses = Course::query()
            ->manageableBy($request->user())
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->with(['category:id,name', 'author:id,first_name,last_name'])
            ->withCount(['chapters', 'assignments'])
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('teacher.courses.index', ['courses' => $courses, 'search' => $search, 'status' => $status]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Course::class);

        return view('teacher.courses.create', [
            'course' => new Course,
            'categories' => Category::optionsFor($request->user()),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = DB::transaction(function () use ($request): Course {
            $course = new Course($request->safe()->only(['title', 'description', 'category_id', 'difficulty', 'sequential_chapters']));
            $course->school_id = $request->user()->school_id;
            $course->author_id = $request->user()->id;
            $course->save();

            $course->modules()->create(['title' => __('Úvod'), 'position' => 0]);

            if ($request->hasFile('cover')) {
                $course->forceFill(['cover_path' => $this->storage->storeCover($request->file('cover'), $course)])->save();
            }

            $this->audit->log(AuditAction::CourseCreated, $course, newValues: $course->only(['title', 'difficulty', 'category_id']));

            return $course;
        });

        return redirect()->route('teacher.courses.show', $course)->with('success', __('Kurz bol vytvorený. Pridajte moduly a kapitoly.'));
    }

    public function show(Course $course): View
    {
        Gate::authorize('update', $course);

        return view('teacher.courses.show', ['course' => $course->load('category')]);
    }

    public function assignments(Course $course): View
    {
        Gate::authorize('assign', $course);

        return view('teacher.courses.assignments', ['course' => $course]);
    }

    public function edit(Request $request, Course $course): View
    {
        Gate::authorize('update', $course);

        return view('teacher.courses.edit', [
            'course' => $course,
            'categories' => Category::optionsFor($request->user()),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $course->fill($request->safe()->only(['title', 'description', 'category_id', 'difficulty', 'sequential_chapters']));

        if ($request->hasFile('cover') || $request->boolean('remove_cover')) {
            $this->storage->delete($course->cover_path);
            $course->cover_path = $request->hasFile('cover') ? $this->storage->storeCover($request->file('cover'), $course) : null;
        }

        $changes = $course->getDirty();
        $old = Arr::only($course->getRawOriginal(), array_keys($changes));
        $course->save();

        if ($changes !== []) {
            $this->audit->log(AuditAction::CourseUpdated, $course, $old, $changes);
        }

        return redirect()->route('teacher.courses.show', $course)->with('success', __('Kurz bol uložený.'));
    }

    public function destroy(Course $course): RedirectResponse
    {
        Gate::authorize('delete', $course);

        $course->delete();
        $this->audit->log(AuditAction::CourseDeleted, $course, $course->only(['title', 'status']));

        return redirect()->route('teacher.courses.index')->with('success', __('Kurz bol odstránený.'));
    }
}
