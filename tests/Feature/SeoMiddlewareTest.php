<?php

namespace Tests\Feature;

use App\Models\Redirect;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_redirects_are_applied_before_route_resolution(): void
    {
        Redirect::create([
            'old_url' => 'old-service',
            'new_url' => '/en/services',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/old-service')
            ->assertRedirect('/en/services');
    }

    public function test_inactive_redirects_are_ignored(): void
    {
        Redirect::create([
            'old_url' => 'old-service',
            'new_url' => '/en/services',
            'status_code' => 301,
            'is_active' => false,
        ]);

        $this->get('/old-service')
            ->assertNotFound();
    }

    public function test_root_redirect_uses_configured_default_language(): void
    {
        SiteSetting::create([
            'key' => 'default_language',
            'value_en' => 'bn',
        ]);

        $this->get('/')
            ->assertRedirect('/bn');
    }
}
