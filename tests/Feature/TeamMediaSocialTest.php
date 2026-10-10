<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeamMediaSocialTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_team_picture_and_social_links(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.teams.store'), [
                'name' => 'Shahriar Rahman',
                'designation_en' => 'Chief Executive Officer',
                'photo' => UploadedFile::fake()->image('profile.jpg', 400, 400),
                'email' => 'leader@example.com',
                'linkedin' => 'https://linkedin.com/in/leader',
                'facebook' => 'https://facebook.com/leader',
                'twitter' => 'https://x.com/leader',
                'website' => 'https://leader.example.com',
                'github' => 'https://github.com/leader',
                'instagram' => 'https://instagram.com/leader',
                'youtube' => 'https://youtube.com/@leader',
                'whatsapp' => 'https://wa.me/8801400000000',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.teams.index'));

        $team = Team::firstOrFail();

        $this->assertSame('leader@example.com', $team->email);
        $this->assertSame('https://facebook.com/leader', $team->facebook);
        $this->assertSame('https://leader.example.com', $team->website);
        $this->assertSame('https://github.com/leader', $team->github);
        $this->assertSame('https://instagram.com/leader', $team->instagram);
        $this->assertSame('https://youtube.com/@leader', $team->youtube);
        $this->assertSame('https://wa.me/8801400000000', $team->whatsapp);
        $this->assertNotEmpty($team->photo);
        Storage::disk('public')->assertExists($team->photo);
    }

    public function test_replacing_or_removing_team_picture_deletes_old_storage_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $oldPhoto = UploadedFile::fake()->image('old.jpg', 300, 300)->store('team-members', 'public');
        $team = Team::create([
            'name' => 'Arifur Chowdhury',
            'photo' => $oldPhoto,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.teams.update', $team), [
                'name' => 'Arifur Chowdhury',
                'photo' => UploadedFile::fake()->image('new.jpg', 300, 300),
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.teams.index'));

        $team->refresh();
        Storage::disk('public')->assertMissing($oldPhoto);
        Storage::disk('public')->assertExists($team->photo);

        $newPhoto = $team->photo;

        $this->actingAs($admin)
            ->put(route('admin.teams.update', $team), [
                'name' => 'Arifur Chowdhury',
                'remove_photo' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.teams.index'));

        $team->refresh();
        $this->assertNull($team->photo);
        Storage::disk('public')->assertMissing($newPhoto);
    }

    public function test_deleting_team_member_removes_picture_from_storage(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);
        $photo = UploadedFile::fake()->image('member.jpg', 300, 300)->store('team-members', 'public');
        $team = Team::create([
            'name' => 'Team Member',
            'photo' => $photo,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.teams.destroy', $team))
            ->assertRedirect(route('admin.teams.index'));

        Storage::disk('public')->assertMissing($photo);
        $this->assertDatabaseMissing('teams', [
            'id' => $team->id,
        ]);
    }

    public function test_frontend_only_shows_filled_team_social_links(): void
    {
        Team::create([
            'name' => 'Linked Leader',
            'designation_en' => 'Leader',
            'linkedin' => 'https://linkedin.com/in/linked-leader',
            'github' => 'https://github.com/linked-leader',
            'is_active' => true,
            'display_order' => 1,
        ]);

        Team::create([
            'name' => 'Plain Leader',
            'designation_en' => 'Leader',
            'is_active' => true,
            'display_order' => 2,
        ]);

        $this->get('/en/about')
            ->assertOk()
            ->assertSee('https://linkedin.com/in/linked-leader', false)
            ->assertSee('https://github.com/linked-leader', false)
            ->assertDontSee('mailto:', false)
            ->assertDontSee('https://facebook.com/plain-leader', false)
            ->assertDontSee('https://instagram.com/plain-leader', false);
    }
}
