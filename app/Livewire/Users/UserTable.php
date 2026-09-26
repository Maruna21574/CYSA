<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\School;
use App\Models\User;
use App\Services\Users\UserManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $role = '';

    #[Url(as: 'school', except: '')]
    public string $schoolId = '';

    #[Url(except: '')]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'role', 'schoolId', 'status'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Activation toggle. The target is loaded through the same scope as the listing and
     * authorized again, so a forged ID from the browser cannot reach another school.
     */
    public function toggleActive(int $userId, UserManager $users): void
    {
        $user = User::manageableBy(auth()->user())->findOrFail($userId);
        $this->authorize('update', $user);

        if ($user->is(auth()->user())) {
            $this->dispatch('toast', type: 'error', message: __('Vlastný účet nemôžete deaktivovať.'));

            return;
        }

        $users->setActive($user, ! $user->is_active);

        $this->dispatch('toast', message: $user->is_active
            ? __('Účet :name bol aktivovaný.', ['name' => $user->name])
            : __('Účet :name bol deaktivovaný.', ['name' => $user->name]));
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function roleOptions(): array
    {
        return collect(UserRole::assignableBy(auth()->user()->role))
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => $role->label()])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function schoolOptions(): array
    {
        return auth()->user()->isSuperAdmin() ? School::orderBy('name')->pluck('name', 'id')->all() : [];
    }

    public function render(): View
    {
        $users = User::query()
            ->manageableBy(auth()->user())
            ->search($this->search)
            ->when(UserRole::tryFrom($this->role), fn ($query, UserRole $role) => $query->where('role', $role))
            ->when($this->schoolId !== '' && auth()->user()->isSuperAdmin(), fn ($query) => $query->where('school_id', (int) $this->schoolId))
            ->when($this->status !== '', fn ($query) => $query->where('is_active', $this->status === 'active'))
            ->with('school:id,name')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(25);

        return view('livewire.users.user-table', ['users' => $users]);
    }
}
