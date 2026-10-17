<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRole;
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

    public function test_non_super_admin_sidebar_hides_super_admin_links(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.services.index'), false)
            ->assertSee(route('admin.seo.dashboard'), false)
            ->assertDontSee('href="'.route('admin.users.index').'"', false)
            ->assertDontSee('href="'.route('admin.roles.index').'"', false)
            ->assertDontSee('href="'.route('admin.audit-logs.index').'"', false)
            ->assertDontSee('href="'.route('admin.cache.index').'"', false)
            ->assertDontSee('href="'.route('admin.settings.index').'"', false)
            ->assertDontSee('href="'.route('admin.seo.scripts').'"', false)
            ->assertDontSee('href="'.route('admin.seo.robots').'"', false)
            ->assertDontSee('href="'.route('admin.redirects.index').'"', false)
            ->assertDontSee('href="'.route('admin.recycle-bin.index').'"', false);
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

    public function test_non_super_admin_cannot_create_super_admin_user(): void
    {
        $admin = User::factory()->create([
            'username' => 'regular_admin',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->withoutMiddleware(EnsureRole::class)
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Second Super Admin',
                'username' => 'second_super_admin',
                'email' => 'second-super-admin@example.com',
                'role' => 'super_admin',
                'is_active' => '1',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Only a super administrator may assign the super administrator role.');

        $this->assertDatabaseMissing('users', [
            'email' => 'second-super-admin@example.com',
        ]);
    }

    public function test_non_super_admin_cannot_promote_user_to_super_admin(): void
    {
        $admin = User::factory()->create([
            'username' => 'regular_admin',
            'role' => 'admin',
            'is_active' => true,
        ]);
        $user = User::factory()->create([
            'username' => 'managed_admin',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->withoutMiddleware(EnsureRole::class)
            ->actingAs($admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => 'super_admin',
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Only a super administrator may assign the super administrator role.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_cannot_deactivate_last_active_super_admin(): void
    {
        $admin = User::factory()->create([
            'username' => 'only_super_admin',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'role' => 'super_admin',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'You cannot deactivate the last remaining active super administrator.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_demote_last_active_super_admin(): void
    {
        $admin = User::factory()->create([
            'username' => 'only_super_admin',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'role' => 'admin',
                'is_active' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'You cannot demote the last remaining active super administrator.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_delete_last_active_super_admin(): void
    {
        $admin = User::factory()->create([
            'username' => 'only_super_admin',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('error', 'You cannot delete the last remaining active super administrator.');

        $this->assertModelExists($admin);
    }
}
