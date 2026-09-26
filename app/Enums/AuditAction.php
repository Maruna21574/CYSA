<?php

namespace App\Enums;

/**
 * Stable identifiers of audited events. The string value is stored in audit_logs.action,
 * so existing values must never be renamed.
 */
enum AuditAction: string
{
    case LoginSucceeded = 'auth.login';
    case LoginFailed = 'auth.login_failed';
    case Logout = 'auth.logout';
    case Lockout = 'auth.lockout';
    case PasswordResetRequested = 'auth.password_reset_requested';
    case PasswordReset = 'auth.password_reset';
    case AccountBlocked = 'auth.account_blocked';

    public function label(): string
    {
        return match ($this) {
            self::LoginSucceeded => __('Prihlásenie'),
            self::LoginFailed => __('Neúspešné prihlásenie'),
            self::Logout => __('Odhlásenie'),
            self::Lockout => __('Dočasné zablokovanie prihlásenia'),
            self::PasswordResetRequested => __('Žiadosť o obnovu hesla'),
            self::PasswordReset => __('Obnova hesla'),
            self::AccountBlocked => __('Prístup deaktivovaného účtu'),
        };
    }
}
