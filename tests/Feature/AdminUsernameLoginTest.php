<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsernameLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_username(): void
    {
        $admin = User::factory()->create([
            'username' => 'site_admin',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post(route('admin.login'), [
            'username' => 'site_admin',
            'password' => 'secret-password',
        ])
            ->assertRedirect('admin/dashboard');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_login_rejects_email_identifier(): void
    {
        User::factory()->create([
            'username' => 'site_admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post(route('admin.login'), [
            'username' => 'admin@example.com',
            'password' => 'secret-password',
        ])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_admin_can_create_user_with_login_username(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Content Admin',
                'username' => 'Content_Admin',
                'email' => 'content@example.com',
                'role' => 'admin',
                'is_active' => '1',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Content Admin',
            'username' => 'content_admin',
            'email' => 'content@example.com',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('content_admin');
    }
}
