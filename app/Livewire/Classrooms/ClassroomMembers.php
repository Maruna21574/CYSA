<?php

namespace App\Livewire\Classrooms;

use App\Enums\AuditAction;
use App\Enums\ClassroomRole;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Adds and removes teachers and students of a classroom. Candidates always come from
 * the classroom's own school and every action is authorized on the server.
 */
class ClassroomMembers extends Component
{
    public Classroom $classroom;

    /** Which kind of member is being added: "student" or "teacher". */
    public string $addRole = 'student';

    public string $search = '';

    public function mount(Classroom $classroom): void
    {
        $this->authorize('view', $classroom);
        $this->classroom = $classroom;
    }

    public function updatedAddRole(): void
    {
        $this->addRole = ClassroomRole::tryFrom($this->addRole)?->value ?? ClassroomRole::Student->value;
        $this->reset('search');
    }

    public function add(int $userId, AuditLogger $audit): void
    {
        $this->authorize('update', $this->classroom);

        $user = $this->candidatesQuery()->findOrFail($userId);
        $role = ClassroomRole::forUser($user->role);

        $this->classroom->members()->syncWithoutDetaching([$user->id => ['role' => $role->value]]);
        $audit->log(AuditAction::ClassroomMemberAdded, $this->classroom, newValues: ['user_id' => $user->id, 'role' => $role->value]);

        unset($this->candidates);
        $this->dispatch('toast', message: __(':name bol(a) pridaný(á) do triedy.', ['name' => $user->name]));
    }

    public function remove(int $userId, AuditLogger $audit): void
    {
        $this->authorize('update', $this->classroom);

        $member = $this->classroom->members()->whereKey($userId)->firstOrFail();

        $this->classroom->members()->detach($member->id);
        $audit->log(AuditAction::ClassroomMemberRemoved, $this->classroom, oldValues: ['user_id' => $member->id, 'role' => $member->pivot->role]);

        $this->dispatch('toast', message: __(':name bol(a) odobratý(á) z triedy.', ['name' => $member->name]));
    }

    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function candidates(): Collection
    {
        if (! auth()->user()->can('update', $this->classroom)) {
            return new Collection;
        }

        return $this->candidatesQuery()
            ->search($this->search)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(10)
            ->get();
    }

    /**
     * Active users of the same school with the requested role who are not members yet.
     *
     * @return Builder<User>
     */
    private function candidatesQuery(): Builder
    {
        $roles = $this->addRole === ClassroomRole::Teacher->value
            ? [UserRole::Teacher, UserRole::SchoolAdmin]
            : [UserRole::Student];

        return User::query()
            ->where('school_id', $this->classroom->school_id)
            ->whereIn('role', $roles)
            ->active()
            ->whereDoesntHave('classrooms', fn ($query) => $query->whereKey($this->classroom->id));
    }

    public function render(): View
    {
        return view('livewire.classrooms.classroom-members', [
            'teachers' => $this->classroom->teachers()->orderBy('last_name')->get(),
            'students' => $this->classroom->students()->orderBy('last_name')->orderBy('first_name')->get(),
            'canManage' => auth()->user()->can('update', $this->classroom),
        ]);
    }
}
