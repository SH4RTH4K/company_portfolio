<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\DeploymentDatabaseService;
use App\Services\DeploymentStore;
use App\Services\GitDeploymentService;
use App\Services\LocalGitDeploymentService;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GitRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private const COMMIT = '0123456789abcdef0123456789abcdef01234567';

    private const PREVIOUS_COMMIT = 'fedcba9876543210fedcba9876543210fedcba98';

    private const RELEASE_ID = '11111111-1111-4111-8111-111111111111';

    public function test_guests_are_redirected_from_every_deployment_action_and_download(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();

        foreach ($this->protectedActions() as [$method, $route, $parameters]) {
            $this->{$method}(route($route, $parameters))->assertRedirect(route('admin.login'));
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertNothingSent();
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_returns_403_for_unprivileged_roles_on_every_deployment_action_and_download(string $role): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['role' => $role, 'is_active' => true]));

        foreach ($this->protectedActions() as [$method, $route, $parameters]) {
            $this->{$method}(route($route, $parameters))->assertForbidden();
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
        Http::assertNothingSent();
    }

    public static function unprivilegedRoles(): array
    {
        return ['admin' => ['admin'], 'editor' => ['editor']];
    }

    public function test_super_admin_can_open_deployment_page_and_menu_during_development_mode(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        SiteSetting::create(['key' => 'development_mode_enabled', 'value_en' => '1']);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.git-repository.index'))
            ->assertViewIs('admin.git_repository.index')
            ->assertSee('Safe workflow')
            ->assertSee('Repository type')
            ->assertSee('Remote name')
            ->assertSee('Run migrations after deployment')
            ->assertSee(route('admin.git-repository.settings'), false);
        $this->get(route('admin.dashboard'))->assertSee(route('admin.git-repository.index'), false);

        Http::assertNothingSent();
    }

    public function test_deployment_screen_uses_the_reference_admin_system_path(): void
    {
        $this->assertSame('/admin/system/git-repository', route('admin.git-repository.index', [], false));
    }

    public function test_reference_settings_are_saved_with_encrypted_private_repository_credentials(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $credential = 'github_pat_private_deploy_example';
        $settings = $this->settings([
            'repository_type' => 'private',
            'authentication' => 'pat',
            'credential' => $credential,
            'php_version' => '8.3',
            'delivery' => 'git',
            'enabled' => false,
            'run_migrations' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.git-repository.settings'), $settings)
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHasNoErrors();

        $encrypted = Storage::disk('local')->get('git-deployment/settings.enc');
        $this->assertStringNotContainsString($credential, $encrypted);
        $this->assertSame($settings, $this->store()->settings());
        $this->get(route('admin.git-repository.index'))
            ->assertViewHas('credentialConfigured', true)
            ->assertDontSee($credential);
        Http::assertNothingSent();
    }

    public function test_server_git_deployment_requires_and_passes_the_exact_reviewed_commit(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $settings = $this->settings(['delivery' => 'git', 'enabled' => true]);
        $store = $this->store();
        $store->saveSettings($settings);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $localGit = Mockery::mock(LocalGitDeploymentService::class);
        $localGit->shouldReceive('deploy')
            ->once()
            ->with($settings, self::COMMIT, $admin->id, $admin->name, false, true)
            ->andReturn(['id' => self::RELEASE_ID, 'health_status' => 'passed']);
        $localGit->shouldReceive('status')->once()->with($settings)->andReturn(['status' => 'Up to date']);
        $this->instance(LocalGitDeploymentService::class, $localGit);

        $this->actingAs($admin)
            ->post(route('admin.git-repository.deploy'), ['commit' => self::COMMIT, 'confirmation' => '1'])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('success', 'Reviewed Git update deployed. Migrations, cache work and the database health check completed.');

        $this->assertSame('Up to date', $store->readData('git-status')['status']);
        Http::assertNothingSent();
    }

    #[DataProvider('serverGitBackupOptions')]
    public function test_server_git_actions_pass_the_selected_database_backup_option(string $action, string $confirmation, bool $discardLocalChanges, array $input, bool $backupDatabase): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $settings = $this->settings(['delivery' => 'git', 'enabled' => true]);
        $this->store()->saveSettings($settings);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $localGit = Mockery::mock(LocalGitDeploymentService::class);
        $localGit->shouldReceive('deploy')->once()
            ->with($settings, self::COMMIT, $admin->id, $admin->name, $discardLocalChanges, $backupDatabase)
            ->andReturn(['id' => self::RELEASE_ID, 'health_status' => 'passed']);
        $localGit->shouldReceive('status')->once()->with($settings)->andReturn(['status' => 'Up to date']);
        $this->instance(LocalGitDeploymentService::class, $localGit);

        $this->actingAs($admin)
            ->post(route('admin.git-repository.'.$action), $input + ['commit' => self::COMMIT, 'confirmation' => $confirmation])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertSame('Up to date', $this->store()->readData('git-status')['status']);
        Http::assertNothingSent();
    }

    #[DataProvider('serverGitActions')]
    public function test_server_git_actions_reject_invalid_database_backup_choices(string $action, string $confirmation, bool $discardLocalChanges): void
    {
        $this->privateStorage();
        $this->store()->saveSettings($this->settings(['delivery' => 'git']));
        $localGit = Mockery::mock(LocalGitDeploymentService::class);
        $localGit->shouldNotReceive('deploy');
        $this->instance(LocalGitDeploymentService::class, $localGit);

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->from(route('admin.git-repository.index'))
            ->post(route('admin.git-repository.'.$action), [
                'commit' => self::COMMIT,
                'confirmation' => $confirmation,
                'backup_database' => 'invalid',
            ])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertInvalid(['backup_database' => 'must be true or false']);

        $this->assertSame([], $this->store()->history());
        Storage::disk('local')->assertMissing('git-deployment/maintenance.json');
    }

    public function test_skipping_the_database_backup_still_requires_deployment_confirmation(): void
    {
        $this->privateStorage();
        $this->store()->saveSettings($this->settings(['delivery' => 'git']));
        $localGit = Mockery::mock(LocalGitDeploymentService::class);
        $localGit->shouldNotReceive('deploy');
        $this->instance(LocalGitDeploymentService::class, $localGit);

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->from(route('admin.git-repository.index'))
            ->post(route('admin.git-repository.deploy'), ['commit' => self::COMMIT, 'backup_database' => '0'])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertInvalid(['confirmation' => 'must be accepted']);

        $this->assertSame([], $this->store()->history());
    }

    public function test_server_git_deployment_form_separates_optional_backup_from_required_confirmation(): void
    {
        $this->privateStorage();
        $this->store()->saveSettings($this->settings(['delivery' => 'git']));
        $status = ['status' => 'Update Available', 'remote' => self::COMMIT, 'checked_at' => now()->toIso8601String()];
        $this->store()->writeData('git-status', $status);
        $localGit = Mockery::mock(LocalGitDeploymentService::class);
        $localGit->shouldReceive('status')->once()->andReturn($status);
        $this->instance(LocalGitDeploymentService::class, $localGit);

        $response = $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->get(route('admin.git-repository.index'))
            ->assertSee('Create a database backup before deployment (optional).');

        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $this->assertSame(1, (int) $xpath->evaluate('count(//input[@id="backup-server-git-deploy" and @name="backup_database" and @checked and not(@required)])'));
        $this->assertSame(1, (int) $xpath->evaluate('count(//input[@id="backup-server-git-deploy"]/../preceding-sibling::input[@type="hidden" and @name="backup_database" and @value="0"])'));
        $this->assertSame(1, (int) $xpath->evaluate('count(//input[@id="confirm-server-git-deploy" and @required])'));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function serverGitActions(): array
    {
        return [
            'reviewed deploy' => ['deploy', '1', false],
            'pull latest' => ['pull', '1', false],
            'discard and deploy' => ['discard-and-deploy', 'DISCARD LOCAL CHANGES', true],
        ];
    }

    /**
     * @return array<string, array{string, string, bool, array<string, string>, bool}>
     */
    public static function serverGitBackupOptions(): array
    {
        $options = [
            'default backup' => [[], true],
            'backup selected' => [['backup_database' => '1'], true],
            'backup skipped' => [['backup_database' => '0'], false],
        ];
        $cases = [];

        foreach (self::serverGitActions() as $action => [$route, $confirmation, $discardLocalChanges]) {
            foreach ($options as $option => [$input, $backupDatabase]) {
                $cases[$action.' '.$option] = [$route, $confirmation, $discardLocalChanges, $input, $backupDatabase];
            }
        }

        return $cases;
    }

    public function test_disabled_integration_blocks_github_checks_without_network_access(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings(['enabled' => false]));

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->post(route('admin.git-repository.inspect'))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'Turn on repository integration in the saved settings before checking GitHub.');

        Http::assertNothingSent();
    }

    public function test_server_review_reports_modified_files_in_the_installed_manifest(): void
    {
        $this->privateStorage();
        $this->store()->saveSettings($this->settings(['enabled' => false, 'delivery' => 'zip']));
        Storage::disk('local')->put('application/app/Models/User.php', 'modified server code');
        config(['deployment.application_path' => Storage::disk('local')->path('application')]);
        Storage::disk('local')->put('installed-manifest.json', json_encode($this->manifest(self::COMMIT, str_repeat('a', 64)), JSON_THROW_ON_ERROR));

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->post(route('admin.git-repository.server.review'))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('success', 'Server state reviewed. Check file integrity and database results below.');

        $review = $this->store()->readData('server-review');
        $this->assertSame('attention', $review['status']);
        $this->assertSame([['path' => 'app/Models/User.php', 'status' => 'modified']], $review['changed_files']);
        $this->assertFalse($review['ok']);
    }

    public function test_reads_php_8_3_release_manifest_on_the_cpanel_runtime(): void
    {
        $this->privateStorage();
        $manifest = $this->manifest(self::COMMIT, str_repeat('a', 64));
        $manifest['php_version'] = '8.3';
        Storage::disk('local')->put('installed-manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        $this->store()->saveSettings($this->settings(['enabled' => false, 'delivery' => 'zip']));

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->get(route('admin.git-repository.index'))
            ->assertViewIs('admin.git_repository.index')
            ->assertSee(self::COMMIT)
            ->assertDontSee('The installed deployment manifest is invalid or incomplete.');
    }

    public function test_super_admin_can_start_a_confirmed_rollback_from_deployment_history(): void
    {
        $this->privateStorage();
        $this->store()->saveRelease($this->release());
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $deployments = Mockery::mock(GitDeploymentService::class);
        $deployments->shouldReceive('rollback')
            ->once()
            ->with(self::RELEASE_ID, $admin->id, $admin->name)
            ->andReturn(['id' => self::RELEASE_ID]);
        $this->instance(GitDeploymentService::class, $deployments);

        $this->actingAs($admin)
            ->post(route('admin.git-repository.rollback', self::RELEASE_ID), ['confirmation' => '1'])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('success', 'Rollback package prepared with a fresh database backup. Upload the selected release through cPanel, then finalize and check the database.');
    }

    public function test_rollback_requires_explicit_confirmation(): void
    {
        $this->privateStorage();
        $deployments = Mockery::mock(GitDeploymentService::class);
        $deployments->shouldNotReceive('rollback');
        $this->instance(GitDeploymentService::class, $deployments);

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->post(route('admin.git-repository.rollback', self::RELEASE_ID))
            ->assertRedirect()
            ->assertSessionHasErrors('confirmation');
    }

    public function test_rollback_prepares_the_verified_package_with_a_fresh_database_backup(): void
    {
        $this->privateStorage();
        $store = $this->store();
        $store->saveSettings($this->settings());
        $previousFile = 'previous release code';
        Storage::disk('local')->put('application/app/Models/User.php', $previousFile);
        config(['deployment.application_path' => Storage::disk('local')->path('application')]);
        $previousManifest = $this->manifest(self::PREVIOUS_COMMIT, hash('sha256', $previousFile));
        $rollbackManifest = $this->manifest(self::COMMIT, str_repeat('b', 64));
        Storage::disk('local')->put('installed-manifest.json', json_encode($previousManifest, JSON_THROW_ON_ERROR));
        $archivePath = 'git-deployment/releases/'.self::RELEASE_ID.'/release.zip';
        $manifestPath = 'git-deployment/releases/'.self::RELEASE_ID.'/manifest.json';
        Storage::disk('local')->put($archivePath, 'verified release archive');
        Storage::disk('local')->put($manifestPath, json_encode($rollbackManifest, JSON_THROW_ON_ERROR));
        $store->saveRelease($this->release([
            'status' => 'completed',
            'approved_at' => '2026-10-24T10:00:00+00:00',
            'archive_path' => $archivePath,
            'manifest_path' => $manifestPath,
            'artifact_id' => 456,
            'cached_artifact_id' => 456,
        ]));
        $database = Mockery::mock(DeploymentDatabaseService::class);
        $database->shouldReceive('check')->once()->with($previousManifest['required_schema'])->andReturn([
            'ok' => true,
            'checks' => [],
            'pending' => [],
            'repairable' => false,
        ]);
        $database->shouldReceive('backup')->once()->andReturn('git-deployment/backups/rollback.sql.gz');
        $this->instance(DeploymentDatabaseService::class, $database);
        Artisan::shouldReceive('call')->times(4)->andReturn(0);

        $rollback = $this->app->make(GitDeploymentService::class)->rollback(self::RELEASE_ID, 9, 'Administrator');

        $this->assertSame('awaiting_verification', $rollback['status']);
        $this->assertSame(self::RELEASE_ID, $rollback['rollback_of']);
        $this->assertSame(self::PREVIOUS_COMMIT, $rollback['rollback_from_commit']);
        $this->assertSame('git-deployment/backups/rollback.sql.gz', $rollback['backup']);
        $this->assertSame('1', SiteSetting::where('key', 'development_mode_enabled')->value('value_en'));
        $this->assertSame($rollback['id'], $store->readData('maintenance')['deployment']);
        $this->assertSame(Storage::disk('local')->path($archivePath), $this->app->make(GitDeploymentService::class)->archive($rollback['id']));
    }

    public function test_deployment_maintenance_blocks_json_public_writes_without_creating_leads(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->writeData('maintenance', ['deployment' => self::RELEASE_ID, 'started_at' => '2026-10-24T10:00:00+00:00']);

        $this->postJson(route('contact.submit', ['locale' => 'en']), ['email' => 'visitor@example.com'])
            ->assertServiceUnavailable()
            ->assertHeader('Retry-After', '60');

        $this->assertDatabaseCount('leads', 0);
        Http::assertNothingSent();
    }

    public function test_deployment_maintenance_blocks_other_admin_writes_and_keeps_deployment_recovery_accessible(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->writeData('maintenance', ['deployment' => self::RELEASE_ID, 'started_at' => '2026-10-24T10:00:00+00:00']);
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));

        $this->post(route('admin.settings.update'), ['company_name' => 'Unexpected change'])
            ->assertServiceUnavailable();
        $this->get(route('admin.git-repository.index'))->assertViewIs('admin.git_repository.index');

        $this->assertDatabaseMissing('site_settings', ['key' => 'company_name', 'value_en' => 'Unexpected change']);
        Http::assertNothingSent();
    }

    public function test_saves_encrypted_credentials_without_exposing_token_in_page_data_or_html(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));
        $settings = $this->settings();

        $this->post(route('admin.git-repository.settings'), $settings + ['unexpected_admin_key' => 'unsafe'])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHasNoErrors();

        $encrypted = Storage::disk('local')->get('git-deployment/settings.enc');
        $this->assertStringNotContainsString($settings['token'], $encrypted);
        $this->assertSame($settings, json_decode(Crypt::decryptString($encrypted), true, flags: JSON_THROW_ON_ERROR));
        $this->assertSame($settings, $this->store()->settings());
        $this->get(route('admin.git-repository.index'))
            ->assertViewHas('tokenConfigured', true)
            ->assertViewHas('settings', fn (array $data): bool => ! array_key_exists('token', $data))
            ->assertDontSee($settings['token']);
        Http::assertNothingSent();
    }

    public function test_retains_blank_token_and_requires_explicit_removal(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings());
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));

        $this->post(route('admin.git-repository.settings'), $this->settings(['token' => '', 'branch' => 'release/main']))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('github_pat_private_example_secret', $this->store()->settings()['token']);
        $this->assertSame('release/main', $this->store()->settings()['branch']);

        $this->post(route('admin.git-repository.settings'), $this->settings(['token' => '', 'remove_token' => '1']))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('', $this->store()->settings()['token']);
        Http::assertNothingSent();
    }

    public function test_normalizes_reference_project_repository_url_before_saving(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));

        $this->post(route('admin.git-repository.settings'), $this->settings([
            'repository' => 'https://github.com/SH4RTH4K/e_commerce_marketplace.git',
        ]))->assertRedirect(route('admin.git-repository.index'))->assertSessionHasNoErrors();

        $this->assertSame('SH4RTH4K/e_commerce_marketplace', $this->store()->settings()['repository']);

        $this->post(route('admin.git-repository.settings'), $this->settings([
            'repository' => 'git@github.com:SH4RTH4K/e_commerce_marketplace.git',
        ]))->assertRedirect(route('admin.git-repository.index'))->assertSessionHasNoErrors();

        $this->assertSame('SH4RTH4K/e_commerce_marketplace', $this->store()->settings()['repository']);
        Http::assertNothingSent();
    }

    public function test_validation_failure_does_not_flash_private_token_into_old_input(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));

        $this->from(route('admin.git-repository.index'))
            ->post(route('admin.git-repository.settings'), $this->settings(['repository' => 'https://gitlab.com/company/portfolio']))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHasErrors(['repository' => 'The repository field format is invalid.'])
            ->assertSessionHasInput('repository', 'https://gitlab.com/company/portfolio')
            ->assertSessionMissingInput('token');

        Storage::disk('local')->assertMissing('git-deployment/settings.enc');
        $this->get(route('admin.git-repository.index'))->assertDontSee('github_pat_private_example_secret');
        Http::assertNothingSent();
    }

    #[DataProvider('invalidSettings')]
    public function test_rejects_invalid_connection_settings_without_changing_saved_credentials(array $changes, string $field, string $message): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings());
        $saved = Storage::disk('local')->get('git-deployment/settings.enc');
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));

        $this->post(route('admin.git-repository.settings'), $this->settings($changes))
            ->assertSessionHasErrors([$field => $message])
            ->assertSessionMissingInput('token');

        $this->assertSame($saved, Storage::disk('local')->get('git-deployment/settings.enc'));
        $this->assertSame([], $this->store()->history());
        Http::assertNothingSent();
    }

    public static function invalidSettings(): array
    {
        return [
            'arbitrary repository URL' => [['repository' => 'https://internal.example/repository'], 'repository', 'The repository field format is invalid.'],
            'repository traversal name' => [['repository' => 'company/..'], 'repository', 'The repository field format is invalid.'],
            'reference traversal' => [['branch' => 'release/../main'], 'branch', 'The branch field format is invalid.'],
            'hidden reference segment' => [['branch' => 'release/.hidden'], 'branch', 'The branch field format is invalid.'],
            'reference query injection' => [['branch' => 'main?token=secret'], 'branch', 'The branch field format is invalid.'],
            'invalid token characters' => [['token' => 'github_pat_ secret'], 'token', 'The token field format is invalid.'],
            'private repository without authentication' => [['repository_type' => 'private', 'authentication' => 'none'], 'authentication', 'Private repositories require an SSH deploy key or personal access token.'],
            'private repository without a credential' => [['repository_type' => 'private', 'authentication' => 'pat'], 'credential', 'A credential is required for a private repository.'],
            'public repository with private authentication' => [['authentication' => 'ssh'], 'authentication', 'Public repositories use None authentication.'],
            'unsupported hosting PHP' => [['php_version' => '8.2'], 'php_version', 'The selected php version is invalid.'],
            'unencrypted file delivery' => [['delivery' => 'ftp'], 'delivery', 'The selected delivery is invalid.'],
        ];
    }

    public function test_repository_preview_and_build_pin_the_reviewed_commit_before_production_confirmation(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings());
        $this->fakeRepository();
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/workflows/deploy-release.yml/dispatches' => Http::response(['workflow_run_id' => 123]),
        ]);
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($admin);

        $this->post(route('admin.git-repository.inspect'))->assertRedirect(route('admin.git-repository.index'));

        $preview = $this->store()->readData('preview');
        $this->assertSame(self::COMMIT, $preview['commit']);
        $this->assertTrue($preview['private']);
        $this->assertArrayHasKey('settings_hash', $preview);
        $this->get(route('admin.git-repository.index'))
            ->assertSee(self::COMMIT)
            ->assertSee('&lt;script&gt;Release&lt;/script&gt;', false)
            ->assertDontSee('<script>Release</script>', false);

        $this->post(route('admin.git-repository.prepare'), ['commit' => self::COMMIT])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHasNoErrors();

        $history = $this->store()->history();
        $this->assertCount(1, $history);
        $this->assertSame(self::COMMIT, $history[0]['commit']);
        $this->assertSame($admin->id, $history[0]['actor_id']);
        $this->assertSame('building', $history[0]['status']);
        $this->assertSame(123, $history[0]['run_id']);
        $this->assertNull($history[0]['approved_at']);
        $this->assertNull($history[0]['backup']);
        Storage::disk('local')->assertMissing('git-deployment/maintenance.json');
        Http::assertSentCount(3);
    }

    public function test_rejects_build_for_commit_that_was_not_shown_in_repository_preview(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings());
        $this->fakeRepository();
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));
        $this->post(route('admin.git-repository.inspect'))->assertRedirect(route('admin.git-repository.index'));

        $this->post(route('admin.git-repository.prepare'), ['commit' => str_repeat('f', 40)])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'Check the repository again, then prepare the commit shown in its preview.');

        $this->assertSame([], $this->store()->history());
        Http::assertSentCount(2);
    }

    public function test_rejects_expired_commit_preview_before_dispatching_a_build(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings());
        $this->fakeRepository();
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));
        $this->post(route('admin.git-repository.inspect'))->assertRedirect(route('admin.git-repository.index'));
        $preview = $this->store()->readData('preview');
        $preview['inspected_at'] = 1;
        $this->store()->writeData('preview', $preview);

        $this->post(route('admin.git-repository.prepare'), ['commit' => self::COMMIT])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'Check the repository again, then prepare the commit shown in its preview.');

        $this->assertSame([], $this->store()->history());
        Http::assertSentCount(2);
    }

    public function test_saving_different_connection_settings_invalidates_previous_commit_preview(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings());
        $this->fakeRepository();
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));
        $this->post(route('admin.git-repository.inspect'))->assertRedirect(route('admin.git-repository.index'));

        $this->post(route('admin.git-repository.settings'), $this->settings(['branch' => 'release/main']))
            ->assertRedirect(route('admin.git-repository.index'));

        Storage::disk('local')->assertMissing('git-deployment/preview.json');
        $this->post(route('admin.git-repository.prepare'), ['commit' => self::COMMIT])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'Check the repository again, then prepare the commit shown in its preview.');
        $this->assertSame([], $this->store()->history());
        Http::assertSentCount(2);
    }

    public function test_refreshes_verified_github_build_into_ready_state_without_deploying_it(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings());
        $this->store()->saveRelease($this->release(['status' => 'building', 'run_id' => 123]));
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/runs/123' => Http::response([
                'id' => 123, 'repository' => ['full_name' => 'company/portfolio'],
                'path' => '.github/workflows/deploy-release.yml', 'event' => 'workflow_dispatch',
                'head_sha' => self::COMMIT, 'status' => 'completed', 'conclusion' => 'success',
            ]),
            'https://api.github.com/repos/company/portfolio/actions/runs/123/artifacts?per_page=100' => Http::response([
                'artifacts' => [['id' => 456, 'name' => 'deployment-release-'.self::COMMIT, 'size_in_bytes' => 100, 'expired' => false]],
            ]),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->post(route('admin.git-repository.refresh', self::RELEASE_ID))
            ->assertRedirect(route('admin.git-repository.index'));

        $release = $this->store()->release(self::RELEASE_ID);
        $this->assertSame('ready', $release['status']);
        $this->assertSame(456, $release['artifact_id']);
        $this->assertSame(123, $release['build_run_id']);
        $this->assertNull($release['approved_at']);
        $this->assertNull($release['backup']);
        Storage::disk('local')->assertMissing('git-deployment/maintenance.json');
        Http::assertSentCount(2);
    }

    public function test_requires_explicit_confirmation_before_deployment_approval(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $release = $this->release();
        $this->store()->saveRelease($release);

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->post(route('admin.git-repository.approve', self::RELEASE_ID), ['commit' => self::COMMIT])
            ->assertSessionHasErrors(['confirmation' => 'The confirmation field must be accepted.']);

        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
        Storage::disk('local')->assertMissing('git-deployment/maintenance.json');
        Http::assertNothingSent();
    }

    public function test_rejects_approval_for_a_different_commit_without_backup_or_maintenance_changes(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $release = $this->release();
        $this->store()->saveRelease($release);

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->post(route('admin.git-repository.approve', self::RELEASE_ID), ['commit' => str_repeat('f', 40), 'confirmation' => '1'])
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'Only a ready release with the exact reviewed commit can be deployed.');

        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
        Storage::disk('local')->assertMissing('git-deployment/maintenance.json');
        Http::assertNothingSent();
    }

    public function test_requires_confirmation_before_finalizing_database_changes(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $release = $this->release(['status' => 'awaiting_verification', 'approved_at' => '2026-10-24T10:00:00+00:00', 'backup' => 'git-deployment/backups/before.sql.gz']);
        $this->store()->saveRelease($release);

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->post(route('admin.git-repository.finalize', self::RELEASE_ID))
            ->assertSessionHasErrors(['confirmation' => 'The confirmation field must be accepted.']);

        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
        Http::assertNothingSent();
    }

    public function test_does_not_download_installable_release_before_deployment_confirmation_and_backup(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $release = $this->release();
        $this->store()->saveRelease($release);

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->from(route('admin.git-repository.index'))
            ->get(route('admin.git-repository.download', self::RELEASE_ID))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'Confirm deployment and create its database backup before downloading the installable release.');

        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
        Http::assertNothingSent();
    }

    public function test_requires_typed_recovery_confirmation_before_exiting_deployment_maintenance(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $release = $this->release(['status' => 'verification_failed', 'approved_at' => '2026-10-24T10:00:00+00:00']);
        $this->store()->saveRelease($release);
        $this->store()->writeData('maintenance', ['deployment' => self::RELEASE_ID]);

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->post(route('admin.git-repository.recover', self::RELEASE_ID), ['confirmation' => '1'])
            ->assertSessionHasErrors(['confirmation' => 'The selected confirmation is invalid.']);

        $this->assertSame($release, $this->store()->release(self::RELEASE_ID));
        Storage::disk('local')->assertExists('git-deployment/maintenance.json');
        Http::assertNothingSent();
    }

    public function test_super_admin_can_download_private_pre_deployment_backup(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $backup = gzencode('CREATE TABLE before_deployment (id INTEGER);');
        $path = 'git-deployment/backups/before.sql.gz';
        Storage::disk('local')->put($path, $backup);
        $this->store()->saveRelease($this->release(['backup' => $path, 'approved_at' => '2026-10-24T10:00:00+00:00']));

        $response = $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->get(route('admin.git-repository.backup', self::RELEASE_ID));

        $response->assertDownload('before-deployment-'.self::RELEASE_ID.'.sql.gz');
        $this->assertSame($backup, $response->streamedContent());
        Http::assertNothingSent();
    }

    #[DataProvider('backupDeletionVisibility')]
    public function test_deployment_history_offers_backup_deletion_only_for_finished_deployments(string $transport, string $status, bool $maintenance, int $expectedForms): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings());
        $this->store()->saveRelease($this->release([
            'transport' => $transport,
            'status' => $status,
            'backup' => 'git-deployment/backups/before.sql.gz',
            'approved_at' => '2026-10-24T10:00:00+00:00',
        ]));
        $this->store()->writeData('maintenance', ['deployment' => $maintenance ? self::RELEASE_ID : null]);

        $response = $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->get(route('admin.git-repository.index'));

        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);
        $action = route('admin.git-repository.backup.delete', self::RELEASE_ID);
        $this->assertSame($expectedForms, (int) $xpath->evaluate('count(//form[@action="'.$action.'"])'));
        $this->assertSame($expectedForms, (int) $xpath->evaluate('count(//form[@action="'.$action.'"]//input[@name="_method" and @value="DELETE"])'));
        $this->assertSame(1, (int) $xpath->evaluate('count(//a[@href="'.$action.'"])'));
        Http::assertNothingSent();
    }

    /**
     * @return array<string, array{string, string, bool, int}>
     */
    public static function backupDeletionVisibility(): array
    {
        return [
            'completed Git deployment' => ['git', 'completed', false, 1],
            'completed package deployment' => ['zip', 'completed', false, 1],
            'package awaiting verification' => ['zip', 'awaiting_verification', false, 0],
            'failed deployment in maintenance' => ['git', 'failed', true, 0],
        ];
    }

    public function test_invalid_installed_manifest_keeps_recovery_page_and_backup_links_available(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings(['delivery' => 'zip']));
        Storage::disk('local')->put('installed-manifest.json', '{incomplete upload');
        $this->store()->saveRelease($this->release(['status' => 'awaiting_verification', 'approved_at' => '2026-10-24T10:00:00+00:00', 'backup' => 'git-deployment/backups/before.sql.gz']));

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->get(route('admin.git-repository.index'))
            ->assertViewHas('installed', [])
            ->assertSee('The installed deployment manifest is invalid or incomplete.')
            ->assertSee('Complete or recover the upload through cPanel File Manager.')
            ->assertSee(route('admin.git-repository.backup', self::RELEASE_ID), false);

        Http::assertNothingSent();
    }

    public function test_database_check_persists_report_and_displays_current_database_results(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]));

        $this->post(route('admin.git-repository.database.check'))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('success', 'Database checks completed. Review the results below.');

        $report = $this->store()->readData('database-report');
        $this->assertTrue($report['ok']);
        $this->assertSame([], $report['pending']);
        $this->assertContains('connection', array_column($report['checks'], 'name'));
        $this->assertContains('permissions', array_column($report['checks'], 'name'));
        $this->get(route('admin.git-repository.index'))
            ->assertViewHas('databaseReport', $report)
            ->assertSee('Database connection and read access succeeded (sqlite).');
        Http::assertNothingSent();
    }

    public function test_active_deployment_older_than_thirty_history_entries_still_blocks_connection_changes(): void
    {
        $this->privateStorage();
        Http::preventStrayRequests();
        $this->store()->saveSettings($this->settings());
        $settingsBefore = Storage::disk('local')->get('git-deployment/settings.enc');
        $this->store()->saveRelease($this->release(['status' => 'awaiting_verification', 'created_at' => '2026-01-01T00:00:00+00:00']));

        for ($index = 0; $index < 31; $index++) {
            $this->store()->saveRelease($this->release(['id' => (string) Str::uuid(), 'status' => 'completed', 'created_at' => '2026-10-24T10:00:00+00:00']));
        }

        $this->actingAs(User::factory()->create(['role' => 'super_admin', 'is_active' => true]))
            ->post(route('admin.git-repository.settings'), $this->settings(['repository' => 'company/another']))
            ->assertRedirect(route('admin.git-repository.index'))
            ->assertSessionHas('error', 'Finish or recover the active deployment before changing repository settings.');

        $this->assertSame($settingsBefore, Storage::disk('local')->get('git-deployment/settings.enc'));
        $this->assertSame('awaiting_verification', $this->store()->release(self::RELEASE_ID)['status']);
        Http::assertNothingSent();
    }

    private function privateStorage(): void
    {
        Storage::fake('local');
        config(['deployment.manifest_path' => Storage::disk('local')->path('installed-manifest.json')]);
    }

    private function store(): DeploymentStore
    {
        return $this->app->make(DeploymentStore::class);
    }

    /**
     * @param  array<string, string|bool>  $changes
     * @return array<string, string|bool>
     */
    private function settings(array $changes = []): array
    {
        return array_replace([
            'repository' => 'company/portfolio',
            'branch' => 'main',
            'remote_name' => 'origin',
            'repository_type' => 'public',
            'authentication' => 'none',
            'credential' => '',
            'run_migrations' => true,
            'clear_cache' => true,
            'health_check' => true,
            'token' => 'github_pat_private_example_secret',
            'php_version' => '8.4',
            'delivery' => 'zip',
            'enabled' => true,
        ], $changes);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function release(array $changes = []): array
    {
        return array_replace([
            'id' => self::RELEASE_ID,
            'repository' => 'company/portfolio',
            'branch' => 'main',
            'commit' => self::COMMIT,
            'php_version' => '8.4',
            'delivery' => 'zip',
            'status' => 'ready',
            'actor_id' => 1,
            'actor' => 'Deployment administrator',
            'created_at' => '2026-10-24T09:00:00+00:00',
            'approved_at' => null,
            'backup' => null,
            'run_id' => 123,
            'artifact_id' => 456,
            'logs' => ['Release build completed.'],
        ], $changes);
    }

    /**
     * @return array{version: int, commit: string, php_version: string, required_schema: array<string, array{columns: list<string>, indexes: list<list<string>>}>, files: array<string, string>}
     */
    private function manifest(string $commit, string $hash): array
    {
        return [
            'version' => 1,
            'commit' => $commit,
            'php_version' => '8.4',
            'required_schema' => ['users' => ['columns' => ['id'], 'indexes' => []]],
            'files' => ['app/Models/User.php' => $hash],
        ];
    }

    private function fakeRepository(): void
    {
        Http::fake([
            'https://api.github.com/repos/company/portfolio' => Http::response(['full_name' => 'company/portfolio', 'private' => true]),
            'https://api.github.com/repos/company/portfolio/commits/main' => Http::response([
                'sha' => self::COMMIT,
                'commit' => ['message' => '<script>Release</script>'],
            ]),
        ]);
    }

    /**
     * @return list<array{string, string, array<string, string>}>
     */
    private function protectedActions(): array
    {
        return [
            ['get', 'admin.git-repository.index', []],
            ['post', 'admin.git-repository.settings', []],
            ['post', 'admin.git-repository.inspect', []],
            ['post', 'admin.git-repository.prepare', []],
            ['post', 'admin.git-repository.deploy', []],
            ['post', 'admin.git-repository.discard-and-deploy', []],
            ['post', 'admin.git-repository.pull', []],
            ['post', 'admin.git-repository.reset-to-remote', []],
            ['post', 'admin.git-repository.database.check', []],
            ['post', 'admin.git-repository.server.review', []],
            ['post', 'admin.git-repository.refresh', ['deployment' => self::RELEASE_ID]],
            ['post', 'admin.git-repository.approve', ['deployment' => self::RELEASE_ID]],
            ['post', 'admin.git-repository.finalize', ['deployment' => self::RELEASE_ID]],
            ['post', 'admin.git-repository.recover', ['deployment' => self::RELEASE_ID]],
            ['post', 'admin.git-repository.rollback', ['deployment' => self::RELEASE_ID]],
            ['get', 'admin.git-repository.download', ['deployment' => self::RELEASE_ID]],
            ['get', 'admin.git-repository.backup', ['deployment' => self::RELEASE_ID]],
            ['delete', 'admin.git-repository.backup.delete', ['deployment' => self::RELEASE_ID]],
        ];
    }
}
