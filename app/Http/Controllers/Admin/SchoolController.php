<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolRequest;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        Gate::authorize('viewAny', School::class);

        return view('admin.schools.index');
    }

    public function create(): View
    {
        Gate::authorize('create', School::class);

        return view('admin.schools.create', ['school' => new School(['is_active' => true])]);
    }

    public function store(SchoolRequest $request): RedirectResponse
    {
        $school = School::create([
            ...$request->validated(),
            'settings' => ['gamification' => (bool) SystemSetting::get('default_gamification', true)],
        ]);

        $this->audit->log(AuditAction::SchoolCreated, $school, newValues: $request->validated());

        return redirect()->route('admin.schools.index')->with('success', __('Škola bola vytvorená.'));
    }

    public function edit(School $school): View
    {
        Gate::authorize('update', $school);

        return view('admin.schools.edit', ['school' => $school]);
    }

    public function update(SchoolRequest $request, School $school): RedirectResponse
    {
        $school->fill($request->validated());
        $changes = $school->getDirty();
        $old = Arr::only($school->getRawOriginal(), array_keys($changes));
        $school->save();

        if ($changes !== []) {
            $this->audit->log(AuditAction::SchoolUpdated, $school, $old, $changes);
        }

        return redirect()->route('admin.schools.index')->with('success', __('Škola bola uložená.'));
    }

    public function destroy(School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);

        if ($school->users()->withTrashed()->exists() || $school->classrooms()->withTrashed()->exists()) {
            return back()->with('error', __('Školu s používateľmi alebo triedami nemožno odstrániť. Namiesto toho ju deaktivujte.'));
        }

        $school->delete();
        $this->audit->log(AuditAction::SchoolDeleted, $school);

        return redirect()->route('admin.schools.index')->with('success', __('Škola bola odstránená.'));
    }
}
