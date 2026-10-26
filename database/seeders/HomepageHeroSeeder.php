<?php

namespace Database\Seeders;

use App\Models\HomepageHero;
use Illuminate\Database\Seeder;

class HomepageHeroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (HomepageHero::query()->doesntExist()) {
            HomepageHero::factory()->create();
        }
    }
}
