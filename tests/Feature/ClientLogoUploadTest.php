<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientLogoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_client_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.clients.store'), [
                'name' => 'Acme Corporation',
                'logo_upload' => UploadedFile::fake()->image('acme.png', 600, 300)->size(256),
                'website' => 'https://acme.example.com',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.clients.index'));

        $client = Client::firstOrFail();

        $this->assertStringStartsWith('client-logos/', $client->logo);
        Storage::disk('public')->assertExists($client->logo);
    }

    public function test_client_logo_upload_must_match_size_limit(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.clients.create'))
            ->post(route('admin.clients.store'), [
                'name' => 'Large Logo Ltd',
                'logo_upload' => UploadedFile::fake()->image('large.png', 600, 300)->size(1200),
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.clients.create'))
            ->assertSessionHasErrors('logo_upload');

        $this->assertDatabaseCount('clients', 0);
        Storage::disk('public')->assertDirectoryEmpty('client-logos');
    }

    public function test_replacing_or_removing_client_logo_deletes_old_uploaded_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $oldLogo = UploadedFile::fake()->image('old.png', 500, 250)->store('client-logos', 'public');
        $client = Client::create([
            'name' => 'Acme Corporation',
            'logo' => $oldLogo,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.clients.update', $client), [
                'name' => 'Acme Corporation',
                'logo_upload' => UploadedFile::fake()->image('new.png', 600, 300)->size(256),
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.clients.index'));

        $client->refresh();
        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertExists($client->logo);

        $newLogo = $client->logo;

        $this->actingAs($admin)
            ->put(route('admin.clients.update', $client), [
                'name' => 'Acme Corporation',
                'remove_logo' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.clients.index'));

        $client->refresh();
        $this->assertNull($client->logo);
        Storage::disk('public')->assertMissing($newLogo);
    }

    public function test_deleting_client_removes_uploaded_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $logo = UploadedFile::fake()->image('client.png', 500, 250)->store('client-logos', 'public');
        $client = Client::create([
            'name' => 'Acme Corporation',
            'logo' => $logo,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.clients.destroy', $client))
            ->assertRedirect(route('admin.clients.index'));

        Storage::disk('public')->assertMissing($logo);
        $this->assertDatabaseMissing('clients', [
            'id' => $client->id,
        ]);
    }
}
