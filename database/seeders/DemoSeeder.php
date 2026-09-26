<?php

namespace Database\Seeders;

use App\Enums\ClassroomRole;
use App\Enums\SchoolType;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo school with one account per role. All demo accounts use the password "password".
 * Courses, classes and results are added to this seeder as the modules are built.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::updateOrCreate(
            ['slug' => 'demo-stredna-skola'],
            ['name' => 'Demo Stredná škola', 'type' => SchoolType::Secondary, 'city' => 'Bratislava', 'is_active' => true],
        );

        $this->user('admin@cysa.test', 'Systémový', 'Administrátor', UserRole::SuperAdmin, null);
        $this->user('skola@cysa.test', 'Eva', 'Horváthová', UserRole::SchoolAdmin, $school);
        $teacher = $this->user('ucitel@cysa.test', 'Peter', 'Novák', UserRole::Teacher, $school);

        $classroom = $school->classrooms()->updateOrCreate(
            ['name' => '4.A', 'school_year' => Classroom::currentSchoolYear()],
            ['grade_level' => 4],
        );
        $classroom->members()->syncWithoutDetaching([$teacher->id => ['role' => ClassroomRole::Teacher->value]]);

        $students = [
            ['Jakub', 'Kováč'], ['Lucia', 'Vargová'], ['Tomáš', 'Tóth'],
            ['Natália', 'Nagyová'], ['Martin', 'Baláž'], ['Simona', 'Molnárová'],
        ];

        foreach ($students as $index => [$firstName, $lastName]) {
            $student = $this->user('student'.($index + 1).'@cysa.test', $firstName, $lastName, UserRole::Student, $school);
            $classroom->members()->syncWithoutDetaching([$student->id => ['role' => ClassroomRole::Student->value]]);
        }
    }

    private function user(string $email, string $firstName, string $lastName, UserRole $role, ?School $school): User
    {
        return User::updateOrCreate(['email' => $email], [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => 'password',
            'role' => $role,
            'school_id' => $school?->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }
}
