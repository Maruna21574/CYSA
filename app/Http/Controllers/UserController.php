<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\Users\UserRequest;
use App\Models\School;
use App\Models\User;
use App\Services\Users\UserManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Account management shared by the super admin (all users) and school admins (own school).
 * The listing itself is the Livewire component Users\UserTable.
 */
class UserController extends Controller
{
    public function __construct(private UserManager $users) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        return view('users.index', ['organization' => $this->organizationContext($request)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        $role = UserRole::tryFrom((string) $request->query('role')) ?? UserRole::Student;
        $organization = $this->organizationContext($request);

        return view('users.create', [
            'user' => new User(['role' => $role, 'is_active' => true, 'school_id' => $organization?->id]),
            'organization' => $organization,
            ...$this->formOptions($request->user(), $organization),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        // Without a password the only way to activate the account is the invitation.
        $invite = blank($request->validated('password')) || $request->boolean('send_invitation');
        $user = $this->users->create($request->userData(), $invite);

        $message = $invite
            ? __('Účet :name bol vytvorený a pozvánka odoslaná na :email.', ['name' => $user->name, 'email' => $user->email])
            : __('Účet :name bol vytvorený.', ['name' => $user->name]);

        return redirect()->route('users.index')->with('success', $message);
    }

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('update', $user);

        $organization = $user->school?->isCompany() ? $user->school : null;

        return view('users.edit', ['user' => $user, 'organization' => $organization, ...$this->formOptions($request->user(), $organization)]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->userData());

        return redirect()->route('users.index')->with('success', __('Zmeny boli uložené.'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);
        abort_if($user->is($request->user()), 403, __('Vlastný účet nemôžete odstrániť.'));

        $this->users->delete($user);

        return redirect()->route('users.index')->with('success', __('Používateľ bol odstránený.'));
    }

    /**
     * GDPR erasure of the account (irreversible).
     */
    public function anonymize(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);
        abort_if($user->is($request->user()), 403, __('Vlastný účet nemôžete anonymizovať.'));

        $request->validate(['confirmation' => ['required', 'in:ANONYMIZOVAŤ']], ['confirmation.in' => __('Na potvrdenie napíšte ANONYMIZOVAŤ.')]);

        $this->users->anonymize($user);

        return redirect()->route('users.index')->with('success', __('Osobné údaje používateľa boli anonymizované.'));
    }

    public function sendInvitation(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $this->users->invite($user);

        return back()->with('success', __('Pozvánka bola odoslaná na :email.', ['email' => $user->email]));
    }

    /**
     * @return array{roles: array<string, string>, schools: array<int, string>}
     */
    private function formOptions(User $actor, ?School $organization = null): array
    {
        return [
            'roles' => collect(UserRole::assignableBy($actor->role))
                ->mapWithKeys(fn (UserRole $role): array => [
                    // Outside an organization the super admin picks the school or company below the role.
                    $role->value => $actor->isSuperAdmin() && ! $organization ? $role->genericLabel() : $role->label($organization),
                ])
                ->all(),
            'schools' => $actor->isSuperAdmin() && ! $organization
                ? School::orderBy('name')->pluck('name', 'id')->all()
                : [],
        ];
    }

    private function organizationContext(Request $request): ?School
    {
        if (! $request->user()->isSuperAdmin()) {
            return $request->user()->school?->isCompany() ? $request->user()->school : null;
        }

        $schoolId = (int) $request->query('school');

        return $schoolId > 0 ? School::companies()->find($schoolId) : null;
    }
}
