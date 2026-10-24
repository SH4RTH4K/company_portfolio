<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavigationSettingsTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('locales')]
    public function test_public_navigation_items_are_visible_by_default(string $locale): void
    {
        $response = $this->get(route('home', ['locale' => $locale]));

        $response->assertOk();
        $xpath = $this->xpath($response);

        foreach ($this->navigationRoutes() as $routeName) {
            $this->assertLinkCount($xpath, '//*[@id="mainNavbar"]', route($routeName, ['locale' => $locale]), 1);
        }

        $this->assertLinkCount($xpath, '//footer', route('services.index', ['locale' => $locale]), 2);
        $this->assertLinkCount($xpath, '//footer', route('contact', ['locale' => $locale]), 1);
    }

    #[DataProvider('locales')]
    public function test_admin_can_hide_selected_header_and_footer_items_and_refresh_cached_settings(string $locale): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $visibility = [
            'home' => '1',
            'about' => '0',
            'services' => '0',
            'managed_it' => '1',
            'products' => '0',
            'portfolio' => '1',
            'industries' => '0',
            'blog' => '1',
            'contact' => '0',
        ];
        $this->get(route('home', ['locale' => $locale]))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), ['menu_visibility' => $visibility])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        foreach ($visibility as $item => $visible) {
            $this->assertDatabaseHas('site_settings', [
                'key' => 'menu_'.$item.'_visible',
                'value_en' => $visible,
                'value_bn' => null,
            ]);
        }

        $response = $this->get(route('home', ['locale' => $locale]));
        $response->assertOk();
        $xpath = $this->xpath($response);

        foreach ($this->navigationRoutes() as $item => $routeName) {
            $url = route($routeName, ['locale' => $locale]);
            $this->assertLinkCount($xpath, '//*[@id="mainNavbar"]', $url, (int) $visibility[$item]);
        }

        $this->assertLinkCount($xpath, '//footer', route('about', ['locale' => $locale]), 0);
        $this->assertLinkCount($xpath, '//footer', route('services.index', ['locale' => $locale]), 0);
        $this->assertLinkCount($xpath, '//footer', route('products.index', ['locale' => $locale]), 0);
        $this->assertLinkCount($xpath, '//footer', route('industries.index', ['locale' => $locale]), 0);
        $this->assertLinkCount($xpath, '//footer', route('contact', ['locale' => $locale]), 0);
        $this->assertLinkCount($xpath, '//footer', route('managed-it', ['locale' => $locale]), 1);
        $this->assertLinkCount($xpath, '//footer', route('portfolio.index', ['locale' => $locale]), 1);
        $this->assertLinkCount($xpath, '//footer', route('blog.index', ['locale' => $locale]), 1);
    }

    public function test_hidden_pages_remain_reachable_and_menu_items_can_be_restored(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), ['menu_visibility' => ['blog' => '0']])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $hiddenPage = $this->get(route('blog.index', ['locale' => 'en']));
        $hiddenPage->assertOk();
        $this->assertLinkCount($this->xpath($hiddenPage), '//*[@id="mainNavbar"]', route('blog.index', ['locale' => 'en']), 0);
        $this->assertLinkCount($this->xpath($hiddenPage), '//footer', route('blog.index', ['locale' => 'en']), 0);

        $this->post(route('admin.settings.update'), ['menu_visibility' => ['blog' => '1']])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'menu_blog_visible', 'value_en' => '1']);
        $restoredPage = $this->get(route('blog.index', ['locale' => 'en']));
        $restoredPage->assertOk();
        $this->assertLinkCount($this->xpath($restoredPage), '//*[@id="mainNavbar"]', route('blog.index', ['locale' => 'en']), 1);
        $this->assertLinkCount($this->xpath($restoredPage), '//footer', route('blog.index', ['locale' => 'en']), 1);
    }

    #[DataProvider('locales')]
    public function test_hiding_all_items_removes_empty_navigation_controls_and_keeps_the_brand(string $locale): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $visibility = array_fill_keys(array_keys($this->navigationRoutes()), '0');

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), ['menu_visibility' => $visibility])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $response = $this->get(route('home', ['locale' => $locale]));
        $response->assertOk();
        $xpath = $this->xpath($response);

        $this->assertSame(0, (int) $xpath->evaluate('count(//*[@id="mainNavbar"])'));
        $this->assertSame(0, (int) $xpath->evaluate('count(//button[@data-bs-target="#mainNavbar"])'));
        $this->assertSame(0, (int) $xpath->evaluate('count(//footer//a[contains(@class, "footer-link")])'));
        $this->assertSame(1, (int) $xpath->evaluate('count(//nav//a[contains(@class, "navbar-brand")])'));
    }

    public function test_settings_saves_preserve_omitted_navigation_items(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        SiteSetting::create(['key' => 'menu_blog_visible', 'value_en' => '0']);
        SiteSetting::create(['key' => 'menu_products_visible', 'value_en' => '0']);

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), ['company_name' => ['en' => 'Updated Company']])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'company_name', 'value_en' => 'Updated Company']);
        $this->assertDatabaseHas('site_settings', ['key' => 'menu_blog_visible', 'value_en' => '0']);
        $this->assertDatabaseHas('site_settings', ['key' => 'menu_products_visible', 'value_en' => '0']);

        $this->post(route('admin.settings.update'), ['menu_visibility' => ['about' => '0']])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('site_settings', ['key' => 'menu_about_visible', 'value_en' => '0']);
        $this->assertDatabaseHas('site_settings', ['key' => 'menu_blog_visible', 'value_en' => '0']);
        $this->assertDatabaseHas('site_settings', ['key' => 'menu_products_visible', 'value_en' => '0']);
    }

    #[DataProvider('invalidVisibility')]
    public function test_invalid_navigation_settings_are_rejected_without_changing_saved_items(mixed $visibility, string $field, string $message): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        SiteSetting::create(['key' => 'menu_home_visible', 'value_en' => '1']);

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.update'), ['menu_visibility' => $visibility])
            ->assertRedirect(route('admin.settings.index'))
            ->assertInvalid([$field => $message]);

        $this->assertDatabaseHas('site_settings', ['key' => 'menu_home_visible', 'value_en' => '1']);
        $this->assertDatabaseMissing('site_settings', ['key' => 'menu_unknown_visible']);
    }

    public function test_settings_form_shows_saved_visibility_and_defaults_for_new_items(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        SiteSetting::create(['key' => 'menu_blog_visible', 'value_en' => '0']);

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertSee('id="navigation-settings"', false);
        $xpath = $this->xpath($response);

        foreach (array_keys($this->navigationRoutes()) as $item) {
            $name = 'menu_visibility['.$item.']';
            $this->assertSame(1, (int) $xpath->evaluate('count(//input[@type="hidden" and @name="'.$name.'" and @value="0"])'));
            $this->assertSame(1, (int) $xpath->evaluate('count(//input[@type="checkbox" and @name="'.$name.'" and @id="menu-item-'.$item.'" and @value="1"])'));
        }

        $this->assertSame(0, (int) $xpath->evaluate('count(//input[@id="menu-item-blog" and @checked])'));
        $this->assertSame(8, (int) $xpath->evaluate('count(//*[@id="navigation-settings"]//input[@type="checkbox" and @checked])'));
    }

    public function test_validation_failure_keeps_submitted_navigation_selections_in_the_form(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        SiteSetting::create(['key' => 'menu_blog_visible', 'value_en' => '1']);

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->post(route('admin.settings.update'), [
                'company_name' => ['en' => str_repeat('a', 256)],
                'menu_visibility' => ['blog' => '0', 'about' => '1'],
            ])
            ->assertRedirect(route('admin.settings.index'))
            ->assertInvalid(['company_name.en']);

        $response = $this->get(route('admin.settings.index'));
        $response->assertOk();
        $xpath = $this->xpath($response);

        $this->assertSame(0, (int) $xpath->evaluate('count(//input[@id="menu-item-blog" and @checked])'));
        $this->assertSame(1, (int) $xpath->evaluate('count(//input[@id="menu-item-about" and @checked])'));
        $this->assertDatabaseHas('site_settings', ['key' => 'menu_blog_visible', 'value_en' => '1']);
    }

    public function test_guests_cannot_update_navigation_settings(): void
    {
        $this->post(route('admin.settings.update'), ['menu_visibility' => ['blog' => '0']])
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('site_settings', ['key' => 'menu_blog_visible']);
    }

    public function test_super_admin_can_open_menu_visibility_from_the_admin_sidebar(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Menu Visibility')
            ->assertSee('href="'.route('admin.settings.index').'#navigation-settings"', false);
    }

    public function test_editors_cannot_update_navigation_settings(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        SiteSetting::create(['key' => 'menu_blog_visible', 'value_en' => '1']);

        $this->actingAs($editor)
            ->post(route('admin.settings.update'), ['menu_visibility' => ['blog' => '0']])
            ->assertForbidden();

        $this->assertDatabaseHas('site_settings', ['key' => 'menu_blog_visible', 'value_en' => '1']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return ['English' => ['en'], 'Bangla' => ['bn']];
    }

    /**
     * @return array<string, array{mixed, string, string}>
     */
    public static function invalidVisibility(): array
    {
        return [
            'non-array visibility' => ['hidden', 'menu_visibility', 'must be an array'],
            'unknown item' => [['home' => '0', 'unknown' => '1'], 'menu_visibility', 'must be an array'],
            'non-boolean item' => [['home' => '0', 'blog' => 'hidden'], 'menu_visibility.blog', 'must be true or false'],
            'empty item' => [['home' => '0', 'blog' => null], 'menu_visibility.blog', 'field is required'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function navigationRoutes(): array
    {
        return [
            'home' => 'home',
            'about' => 'about',
            'services' => 'services.index',
            'managed_it' => 'managed-it',
            'products' => 'products.index',
            'portfolio' => 'portfolio.index',
            'industries' => 'industries.index',
            'blog' => 'blog.index',
            'contact' => 'contact',
        ];
    }

    private function xpath(TestResponse $response): DOMXPath
    {
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);

        return new DOMXPath($document);
    }

    private function assertLinkCount(DOMXPath $xpath, string $scope, string $url, int $expected): void
    {
        $this->assertSame($expected, (int) $xpath->evaluate('count('.$scope.'//a[@href="'.$url.'"])'), $scope.' links to '.$url);
    }
}
