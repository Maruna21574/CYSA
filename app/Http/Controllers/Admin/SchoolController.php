<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\SchoolType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolRequest;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Schools (admin.schools.*) and companies (admin.organizations.*). Both are stored as
 * schools; the section is given by the route, and a record is only reachable in its own
 * section.
 */
class SchoolController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        Gate::authorize('viewAny', School::class);

        return view('admin.schools.index', ['companies' => self::inCompanySection()]);
    }

    public function create(): View
    {
        Gate::authorize('create', School::class);

        return view('admin.schools.create', [
            'school' => new School(['is_active' => true, 'type' => self::inCompanySection() ? SchoolType::Company : null]),
            'companies' => self::inCompanySection(),
        ]);
    }

    public function store(SchoolRequest $request): RedirectResponse
    {
        $school = School::create([
            ...$request->validated(),
            'settings' => ['gamification' => (bool) SystemSetting::get('default_gamification', true)],
        ]);

        $this->audit->log(AuditAction::SchoolCreated, $school, newValues: $request->validated());

        return redirect()->route($this->indexRoute())
            ->with('success', self::inCompanySection() ? __('Organizácia bola vytvorená.') : __('Škola bola vytvorená.'));
    }

    public function edit(School $school): View
    {
        Gate::authorize('update', $school);
        $this->ensureInSection($school);

        return view('admin.schools.edit', ['school' => $school, 'companies' => self::inCompanySection()]);
    }

    public function update(SchoolRequest $request, School $school): RedirectResponse
    {
        $this->ensureInSection($school);

        $school->fill($request->validated());
        $changes = $school->getDirty();
        $old = Arr::only($school->getRawOriginal(), array_keys($changes));
        $school->save();

        if ($changes !== []) {
            $this->audit->log(AuditAction::SchoolUpdated, $school, $old, $changes);
        }

        return redirect()->route($this->indexRoute())
            ->with('success', self::inCompanySection() ? __('Organizácia bola uložená.') : __('Škola bola uložená.'));
    }

    public function destroy(School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);
        $this->ensureInSection($school);

        if ($school->users()->withTrashed()->exists() || $school->classrooms()->withTrashed()->exists()) {
            return back()->with('error', self::inCompanySection()
                ? __('Organizáciu so zamestnancami alebo oddeleniami nemožno odstrániť. Namiesto toho ju deaktivujte.')
                : __('Školu s používateľmi alebo triedami nemožno odstrániť. Namiesto toho ju deaktivujte.'));
        }

        $school->delete();
        $this->audit->log(AuditAction::SchoolDeleted, $school);

        return redirect()->route($this->indexRoute())
            ->with('success', self::inCompanySection() ? __('Organizácia bola odstránená.') : __('Škola bola odstránená.'));
    }

    /**
     * Whether the current request belongs to the organizations (companies) section.
     */
    public static function inCompanySection(): bool
    {
        return request()->routeIs('admin.organizations.*');
    }

    private function indexRoute(): string
    {
        return self::inCompanySection() ? 'admin.organizations.index' : 'admin.schools.index';
    }

    /**
     * A company is edited only under Organizations, a school only under Schools.
     */
    private function ensureInSection(School $school): void
    {
        abort_unless($school->isCompany() === self::inCompanySection(), 404);
    }
}
