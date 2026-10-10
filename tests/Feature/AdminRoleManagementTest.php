<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_separates_users_and_roles(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Roles')
            ->assertSee(route('admin.roles.index'), false)
            ->assertDontSee('Users & Roles');
    }

    public function test_admin_can_create_role_and_assign_it_to_a_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.roles.store'), [
                'name' => 'Operations Manager',
                'slug' => 'operations_manager',
                'description' => 'Manages internal operations.',
                'is_active' => '1',
                'sort_order' => '70',
            ])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('roles', [
            'name' => 'Operations Manager',
            'slug' => 'operations_manager',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Operations Manager');

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Operations User',
                'username' => 'operations',
                'email' => 'operations@example.com',
                'role' => 'operations_manager',
                'is_active' => '1',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'operations',
            'email' => 'operations@example.com',
            'role' => 'operations_manager',
        ]);
    }

    public function test_admin_cannot_delete_role_that_is_assigned_to_users(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $role = Role::create([
            'name' => 'Support Manager',
            'slug' => 'support_manager',
            'is_active' => true,
            'sort_order' => 80,
        ]);

        User::factory()->create([
            'role' => $role->slug,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect()
            ->assertSessionHas('error', 'This role is assigned to users and cannot be deleted.');

        $this->assertDatabaseHas('roles', [
            'slug' => 'support_manager',
        ]);
    }
}
