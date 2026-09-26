<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Sets a new password from a reset link, or the first password from an account invitation.
 * Invitations use their own password broker with a longer token lifetime.
 */
#[Layout('layouts::guest')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    #[Locked]
    public bool $isInvitation = false;

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @param  bool  $invitation  set by the route default of the invitation.accept route
     */
    public function mount(string $token, bool $invitation = false): void
    {
        $this->token = $token;
        $this->isInvitation = $invitation;
        $this->email = (string) request()->string('email');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ];
    }

    public function resetPassword(): void
    {
        $this->validate();

        $status = Password::broker($this->isInvitation ? 'invitations' : null)->reset(
            [
                'email' => Str::lower(trim($this->email)),
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        session()->flash('status', __($status));

        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.auth.reset-password')
            ->title($this->isInvitation ? __('Aktivácia účtu') : __('Nové heslo'));
    }
}
