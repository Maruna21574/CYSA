<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Users\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * "Prepnúť účet" - a higher role views the application as a lower-role account and returns back.
 */
class ImpersonationController extends Controller
{
    public function __construct(private Impersonation $impersonation) {}

    public function index(Request $request): View
    {
        $realUser = $this->impersonation->realUser();
        abort_unless($realUser && $this->impersonation->canSwitch($realUser), 403);

        $roles = collect(UserRole::cases())->reject(fn (UserRole $role): bool => $role === UserRole::SuperAdmin);
        $role = UserRole::tryFrom((string) $request->query('role'));
        $term = mb_substr(trim((string) $request->query('q')), 0, 100);

        $users = Impersonation::candidatesFor($realUser)
            ->with('school')
            ->when($role, fn ($query) => $query->where('role', $role->value))
            ->search($term)
            ->orderByRaw("case role when 'school_admin' then 1 when 'teacher' then 2 else 3 end")
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('impersonation.index', [
            'users' => $users,
            'roles' => $roles,
            'role' => $role,
            'term' => $term,
            'realUser' => $realUser,
        ]);
    }

    public function start(User $user): RedirectResponse
    {
        $this->impersonation->start($user);

        return redirect()->route($user->role->dashboardRoute())
            ->with('success', __('Teraz ste prihlásený ako :name (:role).', ['name' => $user->name, 'role' => $user->role->label($user->school)]));
    }

    public function stop(Request $request): RedirectResponse
    {
        if (! $this->impersonation->stop()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return redirect()->route('impersonation.index')->with('success', __('Vrátili ste sa na svoj účet.'));
    }
}
