<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Sidebar items per role. Items whose route does not exist yet (modules built in later phases)
 * are skipped, so the menu grows automatically as routes are added.
 */
class Navigation
{
    /**
     * @return list<array{label: string, route: string, icon: string, active: string}>
     */
    public static function for(User $user): array
    {
        $items = match ($user->role) {
            UserRole::SuperAdmin => [
                ['label' => __('Prehľad'), 'route' => 'admin.dashboard', 'icon' => 'home', 'active' => 'admin.dashboard'],
                ['label' => __('Školy'), 'route' => 'admin.schools.index', 'icon' => 'building', 'active' => 'admin.schools.*'],
                ['label' => __('Používatelia'), 'route' => 'admin.users.index', 'icon' => 'users', 'active' => 'admin.users.*'],
                ['label' => __('Audit log'), 'route' => 'admin.audit-logs.index', 'icon' => 'document', 'active' => 'admin.audit-logs.*'],
                ['label' => __('Nastavenia'), 'route' => 'admin.settings.edit', 'icon' => 'adjustments', 'active' => 'admin.settings.*'],
            ],
            UserRole::SchoolAdmin => [
                ['label' => __('Prehľad školy'), 'route' => 'school.dashboard', 'icon' => 'home', 'active' => 'school.dashboard'],
                ['label' => __('Učitelia'), 'route' => 'school.teachers.index', 'icon' => 'users', 'active' => 'school.teachers.*'],
                ['label' => __('Študenti'), 'route' => 'school.students.index', 'icon' => 'users', 'active' => 'school.students.*'],
                ['label' => __('Triedy'), 'route' => 'school.classrooms.index', 'icon' => 'squares', 'active' => 'school.classrooms.*'],
                ...self::teacherItems(),
            ],
            UserRole::Teacher => [
                ['label' => __('Prehľad'), 'route' => 'teacher.dashboard', 'icon' => 'home', 'active' => 'teacher.dashboard'],
                ...self::teacherItems(),
            ],
            UserRole::Student => [
                ['label' => __('Prehľad'), 'route' => 'student.dashboard', 'icon' => 'home', 'active' => 'student.dashboard'],
                ['label' => __('Moje kurzy'), 'route' => 'student.courses.index', 'icon' => 'book', 'active' => 'student.courses.*'],
                ['label' => __('Moje výsledky'), 'route' => 'student.results.index', 'icon' => 'chart', 'active' => 'student.results.*'],
                ['label' => __('Certifikáty'), 'route' => 'student.certificates.index', 'icon' => 'badge', 'active' => 'student.certificates.*'],
            ],
        };

        return array_values(array_filter($items, fn (array $item): bool => Route::has($item['route'])));
    }

    /**
     * @return list<array{label: string, route: string, icon: string, active: string}>
     */
    private static function teacherItems(): array
    {
        return [
            ['label' => __('Kurzy'), 'route' => 'teacher.courses.index', 'icon' => 'book', 'active' => 'teacher.courses.*'],
            ['label' => __('Banka otázok'), 'route' => 'teacher.questions.index', 'icon' => 'question', 'active' => 'teacher.questions.*'],
            ['label' => __('Testy'), 'route' => 'teacher.quizzes.index', 'icon' => 'shield', 'active' => 'teacher.quizzes.*'],
            ['label' => __('Analytika'), 'route' => 'teacher.analytics.index', 'icon' => 'chart', 'active' => 'teacher.analytics.*'],
        ];
    }
}
