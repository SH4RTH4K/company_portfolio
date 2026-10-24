<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NavigationAccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('publicPages')]
    public function test_public_pages_are_accessible_by_default(string $routeName, string $locale): void
    {
        $this->get(route($routeName, ['locale' => $locale]))->assertOk();
    }

    #[DataProvider('disabledPages')]
    public function test_disabled_menu_items_return_404_for_direct_page_links(string $item, string $routeName, string $locale): void
    {
        SiteSetting::create(['key' => 'menu_'.$item.'_visible', 'value_en' => '0']);

        $this->get(route($routeName, ['locale' => $locale]))->assertNotFound();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  array<string, string|bool>  $attributes
     */
    #[DataProvider('detailPages')]
    public function test_disabling_menu_items_blocks_existing_detail_pages_and_refreshes_cached_settings(string $item, string $routeName, string $modelClass, array $attributes, string $locale): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $modelClass::create($attributes + ['slug_en' => 'existing-item', 'slug_bn' => 'existing-item-bn']);
        $slug = $locale === 'bn' ? 'existing-item-bn' : 'existing-item';
        $url = route($routeName, ['locale' => $locale, 'slug' => $slug]);
        $this->get($url)->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), ['menu_visibility' => [$item => '0']])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->get($url)->assertNotFound();
    }

    #[DataProvider('locales')]
    public function test_disabled_contact_form_returns_404_without_storing_a_lead_or_sending_mail(string $locale): void
    {
        SiteSetting::create(['key' => 'menu_contact_visible', 'value_en' => '0']);
        Mail::fake();

        $this->post(route('contact.submit', ['locale' => $locale]), [
            'full_name' => 'Contact Visitor',
            'email' => 'visitor@example.com',
            'message' => 'Please send a quote.',
        ])->assertNotFound();

        $this->assertDatabaseEmpty('leads');
        Mail::assertNothingOutgoing();
    }

    #[DataProvider('locales')]
    public function test_disabled_contact_form_returns_404_for_json_without_storing_a_lead_or_sending_mail(string $locale): void
    {
        SiteSetting::create(['key' => 'menu_contact_visible', 'value_en' => '0']);
        Mail::fake();

        $this->postJson(route('contact.submit', ['locale' => $locale]), [
            'full_name' => 'Contact Visitor',
            'email' => 'visitor@example.com',
            'message' => 'Please send a quote.',
        ])->assertNotFound();

        $this->assertDatabaseEmpty('leads');
        Mail::assertNothingOutgoing();
    }

    public function test_admin_settings_remain_accessible_when_all_public_menu_items_are_disabled(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        foreach (array_keys(self::navigationRoutes()) as $item) {
            SiteSetting::create(['key' => 'menu_'.$item.'_visible', 'value_en' => '0']);
        }

        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertSee('id="navigation-settings"', false);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function publicPages(): array
    {
        $pages = [];
        foreach (['en', 'bn'] as $locale) {
            foreach (self::navigationRoutes() as $item => $routeName) {
                $pages[$item.' '.$locale] = [$routeName, $locale];
            }
        }

        return $pages;
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function disabledPages(): array
    {
        $pages = [];
        foreach (['en', 'bn'] as $locale) {
            foreach (self::navigationRoutes() as $item => $routeName) {
                $pages[$item.' '.$locale] = [$item, $routeName, $locale];
            }
        }

        return $pages;
    }

    /**
     * @return array<string, array{string, string, class-string<Model>, array<string, string|bool>, string}>
     */
    public static function detailPages(): array
    {
        $sections = [
            'services' => ['services.show', Service::class, [
                'title_en' => 'Existing Service',
                'short_description_en' => 'Service summary',
                'short_description_bn' => 'Service summary Bangla',
                'status' => 'published',
                'is_active' => true,
            ]],
            'products' => ['products.show', Product::class, [
                'product_name_en' => 'Existing Product',
                'short_description_en' => 'Product summary',
                'short_description_bn' => 'Product summary Bangla',
                'is_active' => true,
            ]],
            'portfolio' => ['portfolio.show', Portfolio::class, [
                'project_name_en' => 'Existing Project',
                'short_description_en' => 'Project summary',
                'short_description_bn' => 'Project summary Bangla',
                'status' => 'published',
            ]],
            'blog' => ['blog.show', Post::class, [
                'title_en' => 'Existing Article',
                'excerpt_en' => 'Article summary',
                'excerpt_bn' => 'Article summary Bangla',
                'status' => 'published',
            ]],
        ];
        $pages = [];
        foreach (['en', 'bn'] as $locale) {
            foreach ($sections as $item => [$routeName, $modelClass, $attributes]) {
                $pages[$item.' '.$locale] = [$item, $routeName, $modelClass, $attributes, $locale];
            }
        }

        return $pages;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return ['English' => ['en'], 'Bangla' => ['bn']];
    }

    /**
     * @return array<string, string>
     */
    private static function navigationRoutes(): array
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
}
