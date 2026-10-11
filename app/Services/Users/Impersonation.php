<?php

namespace App\Services\Users;

use App\Enums\AuditAction;
use App\Enums\ClassroomRole;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * "Switch to account": a higher role signs in as a concrete account of a lower role and
 * can always return. Permissions are always evaluated for the original (real) user, so
 * switching never grants more than that user already has - a student can never switch.
 */
class Impersonation
{
    public const SESSION_KEY = 'impersonator_id';

    public function __construct(private AuditLogger $audit) {}

    public function isActive(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    /**
     * The person actually sitting at the computer.
     */
    public function realUser(): ?User
    {
        $impersonatorId = session(self::SESSION_KEY);

        return $impersonatorId ? User::find($impersonatorId) : Auth::user();
    }

    /**
     * Accounts the given (real) user may switch to.
     * Super admin: everybody else; school admin: teachers and students of the own school;
     * teacher: students of the classrooms they teach; student: nobody.
     *
     * @return Builder<User>
     */
    public static function candidatesFor(User $actor): Builder
    {
        $query = User::query()
            ->where('is_active', true)
            ->whereKeyNot($actor->getKey())
            ->where('role', '!=', UserRole::SuperAdmin->value)
            ->where(fn (Builder $query) => $query->whereNull('school_id')->orWhereHas('school', fn (Builder $school) => $school->where('is_active', true)));

        return match ($actor->role) {
            UserRole::SuperAdmin => $query,
            UserRole::SchoolAdmin => $query
                ->where('school_id', $actor->school_id)
                ->whereIn('role', [UserRole::Teacher->value, UserRole::Student->value]),
            UserRole::Teacher => $query
                ->where('role', UserRole::Student->value)
                ->where('school_id', $actor->school_id)
                ->whereHas('classrooms', fn (Builder $classroom) => $classroom
                    ->whereIn('classrooms.id', $actor->classrooms()->wherePivot('role', ClassroomRole::Teacher->value)->pluck('classrooms.id'))),
            UserRole::Student => $query->whereRaw('1 = 0'),
        };
    }

    public function canSwitch(User $actor): bool
    {
        return $actor->role !== UserRole::Student;
    }

    public function canSwitchTo(User $actor, User $target): bool
    {
        return self::candidatesFor($actor)->whereKey($target->getKey())->exists();
    }

    /**
     * Signs in as the target. When already switched, the original user stays remembered,
     * so "back" always returns to the real account.
     */
    public function start(User $target): void
    {
        $realUser = $this->realUser();

        abort_unless($realUser && $this->canSwitchTo($realUser, $target), 403);

        // The key is set before the login, so the listener does not record it as a real sign-in.
        session()->put(self::SESSION_KEY, $realUser->getKey());
        Auth::guard('web')->login($target);
        session()->regenerate();

        $this->audit->log(AuditAction::ImpersonationStarted, $target, user: $realUser);
    }

    /**
     * Returns to the original account. Returns false when that account can no longer sign in
     * (the caller then logs out completely).
     */
    public function stop(): bool
    {
        $impersonated = Auth::user();
        $realUser = $this->realUser();

        if (! $this->isActive() || ! $realUser?->canSignIn()) {
            return false;
        }

        Auth::guard('web')->login($realUser);
        session()->forget(self::SESSION_KEY);
        session()->regenerate();

        $this->audit->log(AuditAction::ImpersonationStopped, $impersonated, user: $realUser);

        return true;
    }
}
