<?php

namespace App\Livewire\Auth;

use App\Enums\AuditAction;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Zabudnuté heslo')]
class ForgotPassword extends Component
{
    #[Validate('required|string|email|max:255')]
    public string $email = '';

    public function sendResetLink(AuditLogger $audit): void
    {
        $this->validate();

        $key = 'password-reset|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key), 'minutes' => ceil(RateLimiter::availableIn($key) / 60)]),
            ]);
        }

        RateLimiter::hit($key, 60 * 10);

        $email = Str::lower(trim($this->email));

        Password::sendResetLink(['email' => $email]);
        $audit->log(AuditAction::PasswordResetRequested, metadata: ['email' => $email]);

        // Always the same answer, so the form cannot be used to find out which e-mails have an account.
        $this->reset('email');
        session()->flash('status', __('Ak k tomuto e-mailu existuje účet, poslali sme naň odkaz na obnovu hesla.'));
    }
}
