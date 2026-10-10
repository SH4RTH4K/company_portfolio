<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamBengaliNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_bengali_team_name(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.teams.store'), [
                'name' => 'Shahriar Rahman',
                'name_bn' => 'শাহরিয়ার রহমান',
                'designation_en' => 'Chief Executive Officer',
                'designation_bn' => 'প্রধান নির্বাহী কর্মকর্তা',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.teams.index'));

        $this->assertDatabaseHas('teams', [
            'name' => 'Shahriar Rahman',
            'name_bn' => 'শাহরিয়ার রহমান',
        ]);
    }

    public function test_bengali_about_page_shows_bengali_team_name(): void
    {
        Team::create([
            'name' => 'Shahriar Rahman',
            'name_bn' => 'শাহরিয়ার রহমান',
            'designation_en' => 'Chief Executive Officer',
            'designation_bn' => 'প্রধান নির্বাহী কর্মকর্তা',
            'bio_en' => 'Leads the company.',
            'bio_bn' => 'কোম্পানির নেতৃত্ব দেন।',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $this->get('/bn/about')
            ->assertOk()
            ->assertSee('শাহরিয়ার রহমান')
            ->assertDontSee('Shahriar Rahman');
    }
}
