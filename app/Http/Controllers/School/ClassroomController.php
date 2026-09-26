<?php

namespace App\Http\Controllers\School;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\School\ClassroomRequest;
use App\Models\Classroom;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Classroom::class);

        $search = trim((string) $request->query('q'));

        $classrooms = Classroom::query()
            ->visibleTo($request->user())
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->withCount(['students', 'teachers'])
            ->orderByDesc('school_year')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('school.classrooms.index', ['classrooms' => $classrooms, 'search' => $search]);
    }

    public function create(): View
    {
        Gate::authorize('create', Classroom::class);

        return view('school.classrooms.create', ['classroom' => new Classroom(['school_year' => Classroom::currentSchoolYear()])]);
    }

    public function store(ClassroomRequest $request): RedirectResponse
    {
        $classroom = $request->user()->school->classrooms()->create($request->validated());

        $this->audit->log(AuditAction::ClassroomCreated, $classroom, newValues: $request->validated());

        return redirect()->route('school.classrooms.show', $classroom)->with('success', __('Trieda bola vytvorená. Teraz do nej pridajte učiteľov a študentov.'));
    }

    public function show(Classroom $classroom): View
    {
        Gate::authorize('view', $classroom);

        return view('school.classrooms.show', ['classroom' => $classroom]);
    }

    public function edit(Classroom $classroom): View
    {
        Gate::authorize('update', $classroom);

        return view('school.classrooms.edit', ['classroom' => $classroom]);
    }

    public function update(ClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $classroom->fill($request->validated());
        $changes = $classroom->getDirty();
        $old = Arr::only($classroom->getRawOriginal(), array_keys($changes));
        $classroom->save();

        if ($changes !== []) {
            $this->audit->log(AuditAction::ClassroomUpdated, $classroom, $old, $changes);
        }

        return redirect()->route('school.classrooms.show', $classroom)->with('success', __('Trieda bola uložená.'));
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        Gate::authorize('delete', $classroom);

        $classroom->delete();
        $this->audit->log(AuditAction::ClassroomDeleted, $classroom);

        return redirect()->route('school.classrooms.index')->with('success', __('Trieda bola odstránená.'));
    }
}
