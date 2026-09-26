<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Prihlásenie')]
class Login extends Component
{
    /** Failed attempts allowed per e-mail + IP before a temporary lockout. */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    #[Validate('required|string|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:255')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate();
        $this->ensureIsNotRateLimited();

        $credentials = ['email' => Str::lower(trim($this->email)), 'password' => $this->password];

        if (! Auth::attemptWhen($credentials, fn (User $user): bool => $user->canSignIn(), $this->remember)) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);
            $this->reset('password');

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirectIntended(route('dashboard'));
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
        ]);
    }

    private function throttleKey(): string
    {
        return 'login|'.Str::transliterate(Str::lower(trim($this->email))).'|'.request()->ip();
    }
}
