<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_is_sent_to_existing_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('email', $user->email)
            ->call('sendResetLink')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_unknown_email_gets_the_same_response(): void
    {
        Notification::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'nobody@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSee(__('Ak k tomuto e-mailu existuje účet, poslali sme naň odkaz na obnovu hesla.'));

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'noveHeslo123')
            ->set('password_confirmation', 'noveHeslo123')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('noveHeslo123', $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create();

        Livewire::test(ResetPassword::class, ['token' => 'invalid-token'])
            ->set('email', $user->email)
            ->set('password', 'noveHeslo123')
            ->set('password_confirmation', 'noveHeslo123')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_weak_password_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::test(ResetPassword::class, ['token' => Password::createToken($user)])
            ->set('email', $user->email)
            ->set('password', 'heslo')
            ->set('password_confirmation', 'heslo')
            ->call('resetPassword')
            ->assertHasErrors('password');
    }
}
