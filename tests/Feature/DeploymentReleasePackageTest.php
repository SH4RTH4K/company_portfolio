<?php

namespace Tests\Feature;

use App\Console\Commands\BuildDeploymentRelease;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

class DeploymentReleasePackageTest extends TestCase
{
    use RefreshDatabase;

    private const COMMIT = '0123456789abcdef0123456789abcdef01234567';

    private ?string $fixtureDirectory = null;

    protected function tearDown(): void
    {
        if ($this->fixtureDirectory !== null) {
            (new Filesystem)->deleteDirectory($this->fixtureDirectory);
        }

        parent::tearDown();
    }

    public function test_packages_exact_commit_schema_and_production_files_without_secrets_or_runtime_data(): void
    {
        $source = $this->releaseFixture();
        Schema::create('release_widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
        });
        $this->registerFixtureCommand($source);

        $this->artisan('deployment:package', ['commit' => self::COMMIT, '--output' => $source.'/release-output'])
            ->assertSuccessful();

        $manifest = json_decode(file_get_contents($source.'/release-output/release-manifest.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1, $manifest['version']);
        $this->assertSame(self::COMMIT, $manifest['commit']);
        $this->assertSame('public', $manifest['document_root']);
        $this->assertSame(['id', 'slug'], $manifest['required_schema']['release_widgets']['columns']);
        $this->assertContains(['slug'], $manifest['required_schema']['release_widgets']['indexes']);
        $this->assertSame(hash('sha256', '<?php echo "application";'), $manifest['files']['app/Release.php']);

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($source.'/release-output/release.zip'));
        $this->assertSame(file_get_contents($source.'/release-output/release-manifest.json'), $archive->getFromName('deployment-manifest.json'));
        $this->assertSame('original public entry point', $archive->getFromName('public/index.php'));
        $this->assertSame('<?php echo "vendor";', $archive->getFromName('vendor/autoload.php'));
        $this->assertSame('built javascript', $archive->getFromName('public/build/assets/app.js'));
        $this->assertSame('translated text', $archive->getFromName('lang/en/messages.php'));

        foreach (['.env', 'storage/app/customer.txt', 'public/storage/customer.jpg', 'public/uploads/customer.jpg', 'public/hot', 'bootstrap/cache/config.php', 'database/database.sqlite', 'database/backup.sql', 'resources/js/app.js', 'resources/css/app.css', 'tests/Feature/SecretTest.php', 'app/.env.production', 'vendor/package/.git/config'] as $excludedPath) {
            $this->assertFalse($archive->locateName($excludedPath), $excludedPath.' must not be shipped.');
            $this->assertArrayNotHasKey($excludedPath, $manifest['files']);
        }

        $archive->close();
        $this->assertSame(hash_file('sha256', $source.'/release-output/release.zip').'  release.zip'.PHP_EOL, file_get_contents($source.'/release-output/release.sha256'));
    }

    public function test_registered_command_rejects_a_branch_name_instead_of_an_exact_commit(): void
    {
        $this->artisan('deployment:package', ['commit' => 'main'])
            ->expectsOutputToContain('full lowercase 40-character Git SHA')
            ->assertFailed();
    }

    #[DataProvider('missingRequiredFiles')]
    public function test_refuses_to_package_without_production_dependencies_or_built_assets(string $path): void
    {
        $source = $this->releaseFixture();
        unlink($source.'/'.$path);
        $this->registerFixtureCommand($source);

        $this->artisan('deployment:package', ['commit' => self::COMMIT, '--output' => $source.'/release-output'])
            ->expectsOutputToContain('Missing regular release file: '.$path)
            ->assertFailed();

        $this->assertFileDoesNotExist($source.'/release-output/release.zip');
    }

    public static function missingRequiredFiles(): array
    {
        return [['vendor/autoload.php'], ['public/build/manifest.json']];
    }

    #[DataProvider('unsafeOutputPaths')]
    public function test_refuses_unsafe_or_self_including_output_directories(string $suffix): void
    {
        $source = $this->releaseFixture();
        $this->registerFixtureCommand($source);

        $this->artisan('deployment:package', ['commit' => self::COMMIT, '--output' => $source.'/'.$suffix])
            ->assertFailed();

        $this->assertFileDoesNotExist($source.'/'.$suffix.'/release.zip');
    }

    public static function unsafeOutputPaths(): array
    {
        return [['public/releases'], ['app/releases'], ['../escaped-release']];
    }

    public function test_refuses_to_overwrite_an_existing_release_package(): void
    {
        $source = $this->releaseFixture();
        mkdir($source.'/release-output');
        file_put_contents($source.'/release-output/release.zip', 'previous release');
        $this->registerFixtureCommand($source);

        $this->artisan('deployment:package', ['commit' => self::COMMIT, '--output' => $source.'/release-output'])
            ->expectsOutputToContain('release output already exists')
            ->assertFailed();

        $this->assertSame('previous release', file_get_contents($source.'/release-output/release.zip'));
    }

    public function test_refuses_a_source_symbolic_link_that_could_copy_external_files(): void
    {
        $source = $this->releaseFixture();
        file_put_contents($source.'/outside-secret.txt', 'private content');

        if (! @symlink($source.'/outside-secret.txt', $source.'/app/linked-secret.php')) {
            $this->markTestSkipped('Creating symbolic links is not available on this host.');
        }

        $this->registerFixtureCommand($source);

        $this->artisan('deployment:package', ['commit' => self::COMMIT, '--output' => $source.'/release-output'])
            ->expectsOutputToContain('symbolic link')
            ->assertFailed();

        $this->assertFileDoesNotExist($source.'/release-output/release.zip');
    }

    public function test_ftps_uploader_preserves_runtime_paths_and_commits_manifest_after_all_other_files(): void
    {
        $script = <<<'PYTHON'
import importlib.util
import io
import json
import hashlib
import posixpath
import sys
import zipfile

sys.dont_write_bytecode = True
spec = importlib.util.spec_from_file_location("deploy_ftps", sys.argv[1])
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
for path in ["../.env", ".env", "storage/customer.jpg", "public/storage/customer.jpg", "public/uploads/customer.jpg", "bootstrap/cache/config.php", "app\nDELE .env", "app/../.env", "vendor/.env.production"]:
    try:
        module.safe_relative_path(path)
    except ValueError:
        continue
    raise AssertionError("Unsafe release path accepted: " + repr(path))
for directory in ["", "/", "../public_html", "site/../../public_html", "site\nDELE .env"]:
    try:
        module.remote_directory(directory)
    except ValueError:
        continue
    raise AssertionError("Unsafe remote directory accepted")
assert module.remote_directory("/home/account/application") == "/home/account/application"
commit = "0123456789abcdef0123456789abcdef01234567"
data = {"app/Release.php": b"code", "public/index.php": b"public entry point"}
manifest = {"version": 1, "commit": commit, "files": {path: hashlib.sha256(content).hexdigest() for path, content in data.items()}}
buffer = io.BytesIO()
with zipfile.ZipFile(buffer, "w") as archive:
    for path, content in data.items():
        archive.writestr(path, content)
    archive.writestr("deployment-manifest.json", json.dumps(manifest))
buffer.seek(0)
class Client:
    directory = "/"
    renamed = []
    uploaded = []
    def cwd(self, directory):
        self.directory = posixpath.normpath(posixpath.join(self.directory, directory))
    def pwd(self):
        return self.directory
    def storbinary(self, command, content):
        self.uploaded.append((self.directory, command, content.read()))
    def rename(self, temporary, destination):
        self.renamed.append((self.directory, temporary, destination))
client = Client()
with zipfile.ZipFile(buffer) as archive:
    entries = module.release_entries(archive, commit)
    module.deliver(client, archive, entries, "/home/account/application", commit)
    try:
        module.release_entries(archive, "f" * 40)
    except ValueError:
        pass
    else:
        raise AssertionError("Unconfirmed commit accepted")
assert [entry[2] for entry in client.renamed] == ["Release.php", "index.php", "deployment-manifest.json"]
assert client.renamed[-1][0] == "/home/account/application"
assert all(".deployment-" in entry[1] for entry in client.uploaded)
assert client.uploaded[1][2] == b"public entry point"
tampered = io.BytesIO()
with zipfile.ZipFile(tampered, "w") as archive:
    archive.writestr("app/Release.php", b"modified after review")
    archive.writestr("public/index.php", data["public/index.php"])
    archive.writestr("deployment-manifest.json", json.dumps(manifest))
tampered.seek(0)
with zipfile.ZipFile(tampered) as archive:
    try:
        module.release_entries(archive, commit)
    except ValueError:
        pass
    else:
        raise AssertionError("A modified release file passed its checksum check")
print("FTPS contracts passed")
PYTHON;
        $process = new Process([PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3', '-c', $script, base_path('.github/scripts/deploy_ftps.py')]);

        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertSame('FTPS contracts passed', trim($process->getOutput()));
    }

    private function releaseFixture(): string
    {
        $this->fixtureDirectory = sys_get_temp_dir().'/company-deployment-package-'.bin2hex(random_bytes(8));
        $files = new Filesystem;

        foreach ([
            'app/Release.php' => '<?php echo "application";',
            'vendor/autoload.php' => '<?php echo "vendor";',
            'public/build/manifest.json' => '{}',
            'public/build/assets/app.js' => 'built javascript',
            'public/index.php' => 'original public entry point',
            'bootstrap/app.php' => '<?php',
            'artisan' => '<?php',
            'composer.json' => '{}',
            'composer.lock' => '{}',
            'lang/en/messages.php' => 'translated text',
            'resources/views/home.blade.php' => 'home view',
            '.env' => 'DATABASE_SECRET=secret',
            'app/.env.production' => 'secret',
            'storage/app/customer.txt' => 'customer data',
            'public/storage/customer.jpg' => 'upload',
            'public/uploads/customer.jpg' => 'upload',
            'public/hot' => 'http://localhost:5173',
            'bootstrap/cache/config.php' => '<?php // production secrets',
            'database/database.sqlite' => 'database contents',
            'database/backup.sql' => 'database backup',
            'resources/js/app.js' => 'unbuilt javascript',
            'resources/css/app.css' => 'unbuilt styles',
            'tests/Feature/SecretTest.php' => '<?php',
            'vendor/package/.git/config' => 'git credentials',
        ] as $relativePath => $contents) {
            $files->ensureDirectoryExists(dirname($this->fixtureDirectory.'/'.$relativePath));
            $files->put($this->fixtureDirectory.'/'.$relativePath, $contents);
        }

        return $this->fixtureDirectory;
    }

    private function registerFixtureCommand(string $source): void
    {
        $command = new class($source) extends BuildDeploymentRelease
        {
            protected $signature = 'deployment:package {commit} {--output=}';

            public function __construct(private string $fixtureSource)
            {
                parent::__construct();
            }

            protected function sourcePath(): string
            {
                return $this->fixtureSource;
            }
        };

        $this->app->make(Kernel::class)->registerCommand($command);
    }
}
