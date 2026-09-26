<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Enums\SchoolType;
use App\Livewire\Admin\SchoolTable;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_school(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.schools.store'), [
                'name' => 'Gymnázium Test',
                'type' => SchoolType::Secondary->value,
                'city' => 'Žilina',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.schools.index'));

        $this->assertDatabaseHas(School::class, ['name' => 'Gymnázium Test', 'slug' => 'gymnazium-test']);
        $this->assertDatabaseHas(AuditLog::class, ['action' => AuditAction::SchoolCreated->value, 'user_id' => $admin->id]);
    }

    public function test_super_admin_can_deactivate_school(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $school = School::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.schools.update', $school), [
                'name' => $school->name,
                'type' => $school->type->value,
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.schools.index'));

        $this->assertFalse($school->fresh()->is_active);
    }

    public function test_school_with_users_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $school = School::factory()->create();
        User::factory()->for($school)->create();

        $this->actingAs($admin)
            ->delete(route('admin.schools.destroy', $school))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($school);
    }

    public function test_school_admin_cannot_manage_schools(): void
    {
        $schoolAdmin = User::factory()->schoolAdmin()->create();

        $this->actingAs($schoolAdmin)->get(route('admin.schools.index'))->assertForbidden();
        $this->actingAs($schoolAdmin)->post(route('admin.schools.store'), ['name' => 'X', 'type' => 'ss'])->assertForbidden();
        $this->actingAs($schoolAdmin)->get(route('admin.schools.edit', $schoolAdmin->school))->assertForbidden();
    }

    public function test_school_list_can_be_searched(): void
    {
        $admin = User::factory()->superAdmin()->create();
        School::factory()->create(['name' => 'Gymnázium Martin']);
        School::factory()->create(['name' => 'Spojená škola Nitra']);

        Livewire::actingAs($admin)
            ->test(SchoolTable::class)
            ->set('search', 'Martin')
            ->assertSee('Gymnázium Martin')
            ->assertDontSee('Spojená škola Nitra');
    }
}
