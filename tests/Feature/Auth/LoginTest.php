<?php

namespace Tests\Feature\Auth;

use App\Enums\AuditAction;
use App\Livewire\Auth\Login;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeLivewire(Login::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_there_is_no_public_registration(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->teacher()->create(['email' => 'ucitel@example.com']);

        Livewire::test(Login::class)
            ->set('email', 'UCITEL@example.com')
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas(AuditLog::class, [
            'action' => AuditAction::LoginSucceeded->value,
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_log_in_with_wrong_password(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email')
            ->assertSet('password', '');

        $this->assertGuest();
        $this->assertDatabaseHas(AuditLog::class, [
            'action' => AuditAction::LoginFailed->value,
            'user_id' => $user->id,
        ]);
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_of_deactivated_school_cannot_log_in(): void
    {
        $user = User::factory()->for(School::factory()->inactive())->create();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'wrong-password')
                ->call('login');
        }

        // Even the correct password is refused while the lockout lasts.
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::Lockout->value]);
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas(AuditLog::class, [
            'action' => AuditAction::Logout->value,
            'user_id' => $user->id,
        ]);
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $user->update(['is_active' => false]);

        $this->get(route('student.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::AccountBlocked->value]);
    }
}
