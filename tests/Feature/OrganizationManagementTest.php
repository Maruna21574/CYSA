<?php

namespace Tests\Feature;

use App\Enums\SchoolType;
use App\Enums\UserRole;
use App\Livewire\Admin\SchoolTable;
use App\Livewire\Users\UserTable;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_an_organization(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.organizations.store'), [
                'name' => 'Cyber firma',
                'city' => 'Bratislava',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.organizations.index'));

        $this->assertDatabaseHas(School::class, [
            'name' => 'Cyber firma',
            'type' => SchoolType::Company->value,
            'slug' => 'cyber-firma',
        ]);
    }

    public function test_school_and_organization_sections_are_separated(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $school = School::factory()->create(['name' => 'Testovacia škola']);
        $organization = School::factory()->create(['name' => 'Testovacia firma', 'type' => SchoolType::Company]);

        $this->actingAs($admin)
            ->get(route('admin.organizations.index'))
            ->assertOk()
            ->assertSee('Testovacia firma')
            ->assertDontSee('Testovacia škola');

        $this->actingAs($admin)
            ->get(route('admin.schools.index'))
            ->assertOk()
            ->assertSee('Testovacia škola')
            ->assertDontSee('Testovacia firma');

        Livewire::actingAs($admin)
            ->test(SchoolTable::class, ['companies' => true])
            ->assertSee('Testovacia firma')
            ->assertDontSee('Testovacia škola');

        $this->actingAs($admin)->get(route('admin.schools.edit', $organization))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.organizations.edit', $school))->assertNotFound();
    }

    public function test_organization_admin_can_create_employee_from_the_form(): void
    {
        $organization = School::factory()->create(['type' => SchoolType::Company]);
        $admin = User::factory()->schoolAdmin()->for($organization)->create();

        // The form must not send the organization - a school admin is bound to their own on the server.
        $this->actingAs($admin)->get(route('users.create'))->assertOk()->assertDontSee('name="school_id"', false);

        $this->post(route('users.store'), [
            'first_name' => 'Eva', 'last_name' => 'Nová', 'email' => 'eva@firma.sk',
            'role' => UserRole::Student->value, 'password' => 'heslo12345',
        ])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));

        $this->assertSame($organization->id, User::where('email', 'eva@firma.sk')->value('school_id'));
    }

    public function test_company_users_see_company_wording_everywhere(): void
    {
        $company = School::factory()->create(['type' => SchoolType::Company]);
        $trainer = User::factory()->teacher()->for($company)->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($trainer);
        $this->assertSame('Priradiť oddeleniu', __('Priradiť triede'));
        $this->assertSame('Zamestnanci', __('Študenti'));
        $this->assertSame('Oddelenie IT', __('Trieda :name', ['name' => 'IT']));

        $this->actingAs($teacher);
        $this->assertSame('Priradiť triede', __('Priradiť triede'));
        $this->assertSame('Študenti', __('Študenti'));
    }

    public function test_organization_dashboard_counts_only_its_own_trainers(): void
    {
        $organization = School::factory()->create(['type' => SchoolType::Company]);
        $admin = User::factory()->schoolAdmin()->for($organization)->create();
        User::factory()->teacher()->for($organization)->create();
        User::factory()->teacher()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('school.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Školitelia', '1', '+ 1 administrátor firmy'])
            ->assertSee(route('users.index', ['role' => 'teacher']), false);
    }

    public function test_organization_employee_list_is_scoped_to_the_selected_organization(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $organization = School::factory()->create(['type' => SchoolType::Company]);
        $otherOrganization = School::factory()->create(['type' => SchoolType::Company]);
        $employee = User::factory()->student()->for($organization)->create(['first_name' => 'Anna', 'last_name' => 'Firma']);
        $otherEmployee = User::factory()->student()->for($otherOrganization)->create(['first_name' => 'Peter', 'last_name' => 'InáFirma']);

        $this->actingAs($admin)
            ->get(route('users.index', ['school' => $organization->id]))
            ->assertOk()
            ->assertSee($employee->email)
            ->assertDontSee($otherEmployee->email);

        Livewire::actingAs($admin)
            ->test(UserTable::class)
            ->set('schoolId', (string) $organization->id)
            ->assertSee($employee->email)
            ->assertDontSee($otherEmployee->email);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'first_name' => 'Nový',
                'last_name' => 'Zamestnanec',
                'email' => 'novy@firma.test',
                'role' => UserRole::Student->value,
                'school_id' => $organization->id,
                'password' => 'bezpecne123',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas(User::class, ['email' => 'novy@firma.test', 'school_id' => $organization->id]);

    }
}
