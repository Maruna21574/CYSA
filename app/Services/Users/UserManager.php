<?php

namespace App\Services\Users;

use App\Enums\AuditAction;
use App\Models\User;
use App\Notifications\AccountInvitation;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Account lifecycle used by the admin screens and the CSV import. Every change is audited.
 */
class UserManager
{
    /** Attributes that never appear in the audit log. */
    private const HIDDEN_IN_AUDIT = ['password', 'remember_token', 'research_code', 'updated_at', 'created_at'];

    /** Hash of a random, immediately discarded secret; computed once so bulk imports stay fast. */
    private ?string $unusablePasswordHash = null;

    public function __construct(private AuditLogger $audit) {}

    /**
     * Without a password the account cannot be used until the user sets one through an invitation
     * or the "forgot password" link.
     *
     * @param  array{first_name: string, last_name: string, email: string, role: mixed, school_id: ?int, password?: ?string}  $data
     */
    public function create(array $data, bool $sendInvitation = false): User
    {
        $password = filled($data['password'] ?? null)
            ? $data['password']
            : $this->unusablePasswordHash ??= Hash::make(Str::password(40));

        $user = DB::transaction(function () use ($data, $password): User {
            $user = User::create([...$data, 'password' => $password]);

            $this->audit->log(AuditAction::UserCreated, $user, newValues: $this->auditable($user->getAttributes()));

            return $user;
        });

        if ($sendInvitation) {
            $this->invite($user);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->fill($data);
        $dirty = $user->getDirty();

        if ($dirty === []) {
            return $user;
        }

        $old = Arr::only($user->getRawOriginal(), array_keys($dirty));

        DB::transaction(function () use ($user, $dirty, $old): void {
            $user->save();

            $this->audit->log(AuditAction::UserUpdated, $user, $this->auditable($old), $this->auditable($dirty));

            if (array_key_exists('role', $dirty)) {
                $this->audit->log(AuditAction::UserRoleChanged, $user, ['role' => $old['role']], ['role' => $user->role->value]);
            }
        });

        return $user;
    }

    public function setActive(User $user, bool $active): void
    {
        if ($user->is_active === $active) {
            return;
        }

        $user->forceFill(['is_active' => $active])->save();

        $this->audit->log($active ? AuditAction::UserActivated : AuditAction::UserDeactivated, $user);
    }

    public function delete(User $user): void
    {
        $user->delete();

        $this->audit->log(AuditAction::UserDeleted, $user, $this->auditable($user->getAttributes()));
    }

    public function invite(User $user): void
    {
        $token = Password::broker('invitations')->createToken($user);

        $user->notify(new AccountInvitation($token));

        $this->audit->log(AuditAction::UserInvited, $user);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function auditable(array $values): array
    {
        return Arr::except($values, self::HIDDEN_IN_AUDIT);
    }
}
