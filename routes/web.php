<?php

use App\Http\Controllers\Admin\AmcPackageController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BusinessDivisionController;
use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GitRepositoryController;
use App\Http\Controllers\Admin\HomepageHeroController;
use App\Http\Controllers\Admin\IndustryController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\NotFoundLogController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\PostCategoryController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProductBrandController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RecycleBinController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\PublicSeoController;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Admin Routes
Route::prefix('admin')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::middleware(['auth', 'admin'])->name('admin.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('homepage-hero', [HomepageHeroController::class, 'index'])->name('homepage-hero.index');
        Route::post('homepage-hero', [HomepageHeroController::class, 'update'])->name('homepage-hero.update');

        Route::resource('business-divisions', BusinessDivisionController::class);
        Route::resource('services', ServiceController::class);
        Route::post('leads/{lead}/reply', [LeadController::class, 'reply'])->name('leads.reply');
        Route::resource('leads', LeadController::class)->only(['index', 'show', 'update', 'destroy']);

        Route::resource('product-categories', ProductCategoryController::class);
        Route::resource('product-brands', ProductBrandController::class);
        Route::resource('products', ProductController::class);

        Route::resource('amc-packages', AmcPackageController::class);

        Route::resource('clients', ClientController::class);
        Route::resource('testimonials', TestimonialController::class);
        Route::resource('partners', PartnerController::class);
        Route::resource('teams', TeamController::class);
        Route::resource('post-categories', PostCategoryController::class);
        Route::resource('posts', PostController::class);
        Route::resource('industries', IndustryController::class);
        Route::resource('portfolios', PortfolioController::class);
        Route::resource('faqs', FaqController::class);

        // SEO Routes
        Route::get('seo/dashboard', [SeoController::class, 'index'])->name('seo.dashboard');
        Route::get('not-found-logs', [NotFoundLogController::class, 'index'])->name('not-found-logs.index');
        Route::post('not-found-logs/clear', [NotFoundLogController::class, 'clear'])->name('not-found-logs.clear');
        Route::delete('not-found-logs/{notFoundLog}', [NotFoundLogController::class, 'destroy'])->name('not-found-logs.destroy');

        Route::middleware('role:super_admin')->group(function () {
            Route::get('seo/robots', [SeoController::class, 'robots'])->name('seo.robots');
            Route::post('seo/robots', [SeoController::class, 'updateRobots'])->name('seo.robots.update');
            Route::get('seo/scripts', [SeoController::class, 'globalScripts'])->name('seo.scripts');
            Route::post('seo/scripts', [SeoController::class, 'updateGlobalScripts'])->name('seo.scripts.update');

            Route::resource('redirects', RedirectController::class)->except('show');

            Route::resource('users', UserController::class);
            Route::resource('roles', RoleController::class);
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::post('audit-logs/clear', [AuditLogController::class, 'clear'])->name('audit-logs.clear');

            // Recycle Bin
            Route::get('recycle-bin', [RecycleBinController::class, 'index'])->name('recycle-bin.index');
            Route::post('recycle-bin/restore', [RecycleBinController::class, 'restore'])->name('recycle-bin.restore');
            Route::delete('recycle-bin/destroy', [RecycleBinController::class, 'destroy'])->name('recycle-bin.destroy');

            // Cache Management
            Route::get('cache', [CacheController::class, 'index'])->name('cache.index');
            Route::post('cache/clear', [CacheController::class, 'clear'])->name('cache.clear');

            Route::get('settings', [SiteSettingController::class, 'index'])->name('settings.index');
            Route::post('settings', [SiteSettingController::class, 'update'])->name('settings.update');
        });
    });
});

Route::prefix('admin/system/git-repository')
    ->name('admin.git-repository.')
    ->middleware(['auth', 'admin', 'role:super_admin'])
    ->group(function () {
        Route::get('/', [GitRepositoryController::class, 'index'])->name('index');
        Route::get('{deployment}/download', [GitRepositoryController::class, 'download'])->whereUuid('deployment')->name('download');
        Route::get('{deployment}/backup', [GitRepositoryController::class, 'backup'])->whereUuid('deployment')->name('backup');
        Route::middleware('throttle:15,1')->group(function () {
            Route::post('settings', [GitRepositoryController::class, 'update'])->name('settings');
            Route::post('inspect', [GitRepositoryController::class, 'inspect'])->name('inspect');
            Route::post('prepare', [GitRepositoryController::class, 'prepare'])->name('prepare');
            Route::post('deploy', [GitRepositoryController::class, 'deployWithGit'])->name('deploy');
            Route::post('discard-and-deploy', [GitRepositoryController::class, 'discardLocalChangesAndDeploy'])->name('discard-and-deploy');
            Route::post('pull', [GitRepositoryController::class, 'pullLatest'])->name('pull');
            Route::post('reset-to-remote', [GitRepositoryController::class, 'resetToRemote'])->name('reset-to-remote');
            Route::post('database/check', [GitRepositoryController::class, 'checkDatabase'])->name('database.check');
            Route::post('server/review', [GitRepositoryController::class, 'reviewServerState'])->name('server.review');
            Route::post('{deployment}/refresh', [GitRepositoryController::class, 'refresh'])->whereUuid('deployment')->name('refresh');
            Route::post('{deployment}/approve', [GitRepositoryController::class, 'approve'])->whereUuid('deployment')->name('approve');
            Route::post('{deployment}/finalize', [GitRepositoryController::class, 'finalize'])->whereUuid('deployment')->name('finalize');
            Route::post('{deployment}/recover', [GitRepositoryController::class, 'recover'])->whereUuid('deployment')->name('recover');
            Route::post('{deployment}/rollback', [GitRepositoryController::class, 'rollback'])->whereUuid('deployment')->name('rollback');
        });
    });

Route::prefix('system-health')
    ->name('admin.system-health.')
    ->middleware(['auth', 'admin', 'role:super_admin'])
    ->group(function () {
        Route::get('/', [SystemHealthController::class, 'index'])->name('index');
        Route::post('backups/{type}', [SystemHealthController::class, 'createBackup'])
            ->where('type', 'database|media|full')
            ->name('backups.create');
        Route::get('backups/{backupId}/download', [SystemHealthController::class, 'download'])
            ->whereNumber('backupId')
            ->name('backups.download');
        Route::get('backups/{backupId}/phpmyadmin', [SystemHealthController::class, 'downloadPhpMyAdminSql'])
            ->whereNumber('backupId')
            ->name('backups.phpmyadmin');
        Route::delete('backups/{backupId}', [SystemHealthController::class, 'delete'])
            ->whereNumber('backupId')
            ->name('backups.delete');
        Route::post('restores/{type}', [SystemHealthController::class, 'restore'])
            ->where('type', 'database|media|full')
            ->name('restore');
        Route::post('cache/clear', [SystemHealthController::class, 'clearCache'])->name('cache.clear');
        Route::post('migrations/run', [SystemHealthController::class, 'runMigrations'])->name('migrations.run');
    });

// Public SEO Routes
Route::get('/robots.txt', [PublicSeoController::class, 'robots']);
Route::get('/sitemap.xml', [PublicSeoController::class, 'sitemap']);

Route::get('/', function () {
    $defaultLocale = 'en';

    try {
        if (Schema::hasTable('site_settings')) {
            $defaultLocale = SiteSetting::where('key', 'default_language')->value('value_en') ?: $defaultLocale;
        }
    } catch (Throwable) {
        $defaultLocale = 'en';
    }

    if (! in_array($defaultLocale, ['en', 'bn'], true)) {
        $defaultLocale = 'en';
    }

    return redirect('/'.$defaultLocale);
});

Route::group(['prefix' => '{locale}', 'where' => ['locale' => 'en|bn']], function () {
    Route::get('/', [FrontendController::class, 'home'])->name('home');
    Route::get('/about', [FrontendController::class, 'about'])->name('about');

    Route::get('/services', [FrontendController::class, 'services'])->name('services.index');
    Route::get('/services/{slug}', [FrontendController::class, 'serviceDetail'])->name('services.show');

    Route::get('/products', [FrontendController::class, 'products'])->name('products.index');
    Route::get('/products/{slug}', [FrontendController::class, 'productDetail'])->name('products.show');

    Route::get('/portfolio', [FrontendController::class, 'portfolio'])->name('portfolio.index');
    Route::get('/portfolio/{slug}', [FrontendController::class, 'portfolioDetail'])->name('portfolio.show');

    Route::get('/industries', [FrontendController::class, 'industries'])->name('industries.index');
    Route::get('/managed-it', [FrontendController::class, 'managedIt'])->name('managed-it');

    Route::get('/blog', [FrontendController::class, 'blog'])->name('blog.index');
    Route::get('/blog/{slug}', [FrontendController::class, 'blogDetail'])->name('blog.show');

    Route::get('/contact', [ContactController::class, 'show'])->name('contact');
    Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');
});
