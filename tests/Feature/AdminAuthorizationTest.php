<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_editor_role_user_is_forbidden_from_super_admin_routes(): void
    {
        $editor = User::factory()->create([
            'role' => 'editor',
            'is_active' => true,
        ]);

        foreach ($this->superAdminRoutes() as $route) {
            $this->actingAs($editor)
                ->get(route($route))
                ->assertForbidden();
        }
    }

    public function test_super_admin_can_access_super_admin_routes(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        foreach ($this->superAdminRoutes() as $route) {
            $this->actingAs($superAdmin)
                ->get(route($route))
                ->assertOk();
        }
    }

    public function test_only_super_admin_can_create_super_admin_user(): void
    {
        $admin = User::factory()->create([
            'username' => 'regular_admin',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->withoutMiddleware(EnsureRole::class)
            ->actingAs($admin)
            ->post(route('admin.users.store'), $this->superAdminUserPayload('blocked_super_admin', 'blocked-super-admin@example.com'))
            ->assertRedirect()
            ->assertSessionHas('error', 'Only a super administrator may assign the super administrator role.');

        $this->assertDatabaseMissing('users', [
            'email' => 'blocked-super-admin@example.com',
        ]);

        $superAdmin = User::factory()->create([
            'username' => 'root_admin',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.store'), $this->superAdminUserPayload('allowed_super_admin', 'allowed-super-admin@example.com'))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'username' => 'allowed_super_admin',
            'email' => 'allowed-super-admin@example.com',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function superAdminRoutes(): array
    {
        return [
            'admin.users.index',
            'admin.roles.index',
            'admin.settings.index',
            'admin.seo.scripts',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function superAdminUserPayload(string $username, string $email): array
    {
        return [
            'name' => 'Super Admin User',
            'username' => $username,
            'email' => $email,
            'role' => 'super_admin',
            'is_active' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }
}
