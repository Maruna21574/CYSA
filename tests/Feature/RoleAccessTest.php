<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Which dashboards each role may open.
     *
     * @return array<string, array{UserRole, list<string>}>
     */
    public static function roleProvider(): array
    {
        return [
            'super admin' => [UserRole::SuperAdmin, ['admin.dashboard']],
            'school admin' => [UserRole::SchoolAdmin, ['school.dashboard', 'teacher.dashboard']],
            'teacher' => [UserRole::Teacher, ['teacher.dashboard']],
            'student' => [UserRole::Student, ['student.dashboard']],
        ];
    }

    #[DataProvider('roleProvider')]
    public function test_dashboard_redirects_to_role_dashboard(UserRole $role): void
    {
        $user = $this->userWithRole($role);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route($role->dashboardRoute()));
    }

    /**
     * @param  list<string>  $allowedRoutes
     */
    #[DataProvider('roleProvider')]
    public function test_role_can_open_only_its_own_areas(UserRole $role, array $allowedRoutes): void
    {
        $user = $this->userWithRole($role);
        $allRoutes = ['admin.dashboard', 'school.dashboard', 'teacher.dashboard', 'student.dashboard'];

        foreach ($allRoutes as $route) {
            $response = $this->actingAs($user)->get(route($route));

            in_array($route, $allowedRoutes, true)
                ? $response->assertOk()
                : $response->assertForbidden();
        }
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get(route('login'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_audit_log_records_cannot_be_modified(): void
    {
        $log = AuditLog::create(['action' => 'test.action']);

        $this->expectException(LogicException::class);

        $log->update(['action' => 'changed']);
    }

    public function test_new_user_gets_unique_research_code(): void
    {
        $users = User::factory()->count(3)->create();

        $this->assertCount(3, $users->pluck('research_code')->filter()->unique());
    }

    private function userWithRole(UserRole $role): User
    {
        return match ($role) {
            UserRole::SuperAdmin => User::factory()->superAdmin()->create(),
            UserRole::SchoolAdmin => User::factory()->schoolAdmin()->create(),
            UserRole::Teacher => User::factory()->teacher()->create(),
            UserRole::Student => User::factory()->student()->create(),
        };
    }
}
