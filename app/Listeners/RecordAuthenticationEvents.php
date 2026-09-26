<?php

namespace App\Listeners;

use App\Enums\AuditAction;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

/**
 * Writes authentication events to the audit log. Discovered automatically by Laravel
 * through the type hints of the handle* methods.
 */
class RecordAuthenticationEvents
{
    public function __construct(private AuditLogger $audit) {}

    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

        $this->audit->log(
            AuditAction::LoginSucceeded,
            auditable: $event->user,
            metadata: ['remember' => $event->remember],
            user: $event->user,
        );
    }

    public function handleFailed(Failed $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        $this->audit->log(
            AuditAction::LoginFailed,
            auditable: $user,
            metadata: ['email' => Str::limit(Str::lower((string) ($event->credentials['email'] ?? '')), 255, '')],
            user: $user,
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $this->audit->log(
            AuditAction::Lockout,
            metadata: ['email' => Str::limit(Str::lower((string) $event->request->input('email', '')), 255, '')],
        );
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->audit->log(AuditAction::Logout, auditable: $event->user, user: $event->user);
    }

    public function handlePasswordReset(PasswordReset $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->audit->log(AuditAction::PasswordReset, auditable: $event->user, user: $event->user);
    }
}
