<?php

namespace Tests\Feature;

use App\Services\GitRepositoryClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class GitRepositoryClientTest extends TestCase
{
    private const COMMIT = '0123456789abcdef0123456789abcdef01234567';

    public function test_inspects_public_repository_without_sending_credentials(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio' => Http::response(['full_name' => 'company/portfolio', 'private' => false]),
            'https://api.github.com/repos/company/portfolio/commits/release%2Fmain' => Http::response([
                'sha' => self::COMMIT,
                'commit' => ['message' => 'Prepare release'],
            ]),
        ]);

        $result = (new GitRepositoryClient)->inspect($this->settings(['branch' => 'release/main', 'token' => null]));

        $this->assertSame([
            'repository' => 'company/portfolio',
            'private' => false,
            'branch' => 'release/main',
            'commit' => self::COMMIT,
            'message' => 'Prepare release',
            'url' => 'https://github.com/company/portfolio/commit/'.self::COMMIT,
        ], $result);
        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $request): bool => $request->hasHeader('Authorization'));
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('X-GitHub-Api-Version', '2026-03-10'));
    }

    public function test_inspects_private_repository_with_token_only_in_authorization_header(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio' => Http::response(['full_name' => 'company/portfolio', 'private' => true]),
            'https://api.github.com/repos/company/portfolio/commits/main' => Http::response(['sha' => self::COMMIT]),
        ]);

        $result = (new GitRepositoryClient)->inspect($this->settings());

        $this->assertTrue($result['private']);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer github_pat_test-secret'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'test-secret') || str_contains($request->body(), 'test-secret'));
    }

    #[DataProvider('unsafeSettings')]
    public function test_rejects_unsafe_repository_and_branch_settings_before_network_access(array $settings): void
    {
        Http::preventStrayRequests();

        try {
            (new GitRepositoryClient)->inspect($this->settings($settings));
            $this->fail('Unsafe repository settings were accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Enter', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public static function unsafeSettings(): array
    {
        return [
            'arbitrary URL' => [['repository' => 'https://internal.example/repo']],
            'URL with credentials' => [['repository' => 'https://secret@github.com/company/portfolio']],
            'path traversal' => [['repository' => 'company/../portfolio']],
            'encoded path traversal' => [['repository' => 'company/%2e%2e']],
            'empty branch' => [['branch' => '']],
            'branch traversal' => [['branch' => '../main']],
            'branch query' => [['branch' => 'main?secret=yes']],
            'branch locked reference' => [['branch' => 'main.lock']],
            'branch control characters' => [['branch' => "main\r\nX-Injected: true"]],
        ];
    }

    #[DataProvider('githubFailures')]
    public function test_reports_safe_guidance_for_github_failures(int $status, array $headers, string $guidance): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio' => Http::response(['message' => 'github_pat_test-secret upstream details'], $status, $headers),
        ]);

        try {
            (new GitRepositoryClient)->inspect($this->settings());
            $this->fail('The failed GitHub response was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString($guidance, $exception->getMessage());
            $this->assertStringNotContainsString('test-secret', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }

        Http::assertSentCount(1);
    }

    public static function githubFailures(): array
    {
        return [
            'expired token' => [401, [], 'rejected the saved token'],
            'permission denied' => [403, [], 'token repository permissions'],
            'rate limited' => [403, ['X-RateLimit-Remaining' => '0'], 'rate limit'],
            'not found or hidden' => [404, [], 'private repository'],
            'request rate limited' => [429, [], 'rate limit'],
            'server failed' => [500, [], 'unexpected response'],
        ];
    }

    public function test_reports_network_failure_without_exposing_exception_details(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio' => Http::failedConnection('github_pat_test-secret internal host details'),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GitHub could not be reached. Check the hosting network connection and try again.');

        (new GitRepositoryClient)->inspect($this->settings());
    }

    public function test_dispatches_reviewed_commit_and_returns_a_trusted_run_url(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/workflows/deploy-release.yml/dispatches' => Http::response([
                'workflow_run_id' => 123,
                'html_url' => 'https://untrusted.example/',
            ]),
        ]);

        $result = (new GitRepositoryClient)->dispatch($this->settings(), self::COMMIT);

        $this->assertSame(['run_id' => 123, 'run_url' => 'https://github.com/company/portfolio/actions/runs/123'], $result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer github_pat_test-secret')
            && $request->data() === [
                'ref' => 'main',
                'return_run_details' => true,
                'inputs' => ['commit' => self::COMMIT, 'php_version' => '8.4', 'delivery' => 'zip', 'artifact_run_id' => ''],
            ]);
    }

    public function test_dispatches_ftps_delivery_using_the_approved_artifact_build_run(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/workflows/deploy-release.yml/dispatches' => Http::response(['workflow_run_id' => 124]),
        ]);

        $result = (new GitRepositoryClient)->dispatch($this->settings(['delivery' => 'ftps', 'artifact_run_id' => 123]), self::COMMIT);

        $this->assertSame(['run_id' => 124, 'run_url' => 'https://github.com/company/portfolio/actions/runs/124'], $result);
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request['inputs'] === [
            'commit' => self::COMMIT,
            'php_version' => '8.4',
            'delivery' => 'ftps',
            'artifact_run_id' => '123',
        ]);
    }

    public function test_dispatches_php_8_3_builds_for_cpanel_hosts(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/workflows/deploy-release.yml/dispatches' => Http::response(['workflow_run_id' => 125]),
        ]);

        (new GitRepositoryClient)->dispatch($this->settings(['php_version' => '8.3']), self::COMMIT);

        Http::assertSent(fn (Request $request): bool => $request['inputs']['php_version'] === '8.3');
    }

    public function test_requires_token_to_dispatch_public_repository_build(): void
    {
        Http::preventStrayRequests();

        try {
            (new GitRepositoryClient)->dispatch($this->settings(['token' => null]), self::COMMIT);
            $this->fail('A build was dispatched without authentication.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('including for public repositories', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    #[DataProvider('invalidBuilds')]
    public function test_rejects_invalid_build_inputs_before_network_access(array $settings, string $commit): void
    {
        Http::preventStrayRequests();

        try {
            (new GitRepositoryClient)->dispatch($this->settings($settings), $commit);
            $this->fail('Invalid workflow inputs were accepted.');
        } catch (RuntimeException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public static function invalidBuilds(): array
    {
        return [
            'short hash' => [[], '0123456'],
            'branch as hash' => [[], 'main'],
            'unsupported PHP' => [['php_version' => '8.2'], self::COMMIT],
            'PHP 8.1 unsupported' => [['php_version' => '8.1'], self::COMMIT],
            'unencrypted FTP' => [['delivery' => 'ftp'], self::COMMIT],
            'token header injection' => [['token' => "github_pat_secret\r\nX-Injected: yes"], self::COMMIT],
            'negative source run ID' => [['delivery' => 'ftps', 'artifact_run_id' => -123], self::COMMIT],
            'zero source run ID' => [['delivery' => 'ftps', 'artifact_run_id' => '0'], self::COMMIT],
            'decimal source run ID' => [['delivery' => 'ftps', 'artifact_run_id' => '123.4'], self::COMMIT],
            'source run ID injection' => [['delivery' => 'ftps', 'artifact_run_id' => '123/../../private'], self::COMMIT],
            'source run ID integer overflow' => [['delivery' => 'ftps', 'artifact_run_id' => '9999999999999999999999999999'], self::COMMIT],
            'source run ID unexpected type' => [['delivery' => 'ftps', 'artifact_run_id' => ['123']], self::COMMIT],
        ];
    }

    public function test_retains_accepted_dispatch_without_guessing_a_run_id_for_204_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/workflows/deploy-release.yml/dispatches' => Http::response('', 204),
        ]);

        $result = (new GitRepositoryClient)->dispatch($this->settings(), self::COMMIT);

        $this->assertSame(['run_id' => null, 'run_url' => null], $result);
        Http::assertSentCount(1);
    }

    public function test_does_not_retry_ambiguous_dispatch_network_failure(): void
    {
        Http::preventStrayRequests();
        $attempts = 0;
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/workflows/deploy-release.yml/dispatches' => function () use (&$attempts) {
                $attempts++;

                return Http::failedConnection('github_pat_test-secret');
            },
        ]);

        try {
            (new GitRepositoryClient)->dispatch($this->settings(), self::COMMIT);
            $this->fail('The ambiguous dispatch was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('avoid starting a duplicate deployment', $exception->getMessage());
            $this->assertStringNotContainsString('test-secret', $exception->getMessage());
        }

        $this->assertSame(1, $attempts);
    }

    public function test_returns_only_usable_release_artifacts_after_successful_run(): void
    {
        Http::preventStrayRequests();
        $artifact = ['id' => 456, 'name' => 'deployment-release-'.self::COMMIT, 'size_in_bytes' => 100, 'expired' => false];
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/runs/123' => Http::response($this->workflowRun()),
            'https://api.github.com/repos/company/portfolio/actions/runs/123/artifacts?per_page=100' => Http::response([
                'artifacts' => [
                    $artifact,
                    array_replace($artifact, ['id' => 457, 'expired' => true]),
                    array_replace($artifact, ['id' => 458, 'name' => 'unrelated-artifact']),
                    array_replace($artifact, ['id' => 459, 'size_in_bytes' => 524288001]),
                ],
            ]),
        ]);

        $result = (new GitRepositoryClient)->run($this->settings(), 123);

        $this->assertSame([
            'run_id' => 123,
            'status' => 'completed',
            'conclusion' => 'success',
            'commit' => self::COMMIT,
            'name' => '',
            'url' => 'https://github.com/company/portfolio/actions/runs/123',
            'artifacts' => [['id' => 456, 'name' => 'deployment-release-'.self::COMMIT, 'size' => 100]],
        ], $result);
        Http::assertSentCount(2);
    }

    public function test_does_not_offer_artifacts_from_failed_run(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/runs/123' => Http::response($this->workflowRun(['conclusion' => 'failure'])),
        ]);

        $result = (new GitRepositoryClient)->run($this->settings(), 123);

        $this->assertSame('failure', $result['conclusion']);
        $this->assertSame([], $result['artifacts']);
        Http::assertSentCount(1);
    }

    #[DataProvider('unrelatedRuns')]
    public function test_rejects_run_outside_saved_deployment_workflow(array $changes): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/runs/123' => Http::response($this->workflowRun($changes)),
        ]);

        try {
            (new GitRepositoryClient)->run($this->settings(), 123);
            $this->fail('An unrelated run was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('does not belong', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public static function unrelatedRuns(): array
    {
        return [
            'another repository' => [['repository' => ['full_name' => 'another/portfolio']]],
            'another workflow' => [['path' => '.github/workflows/tests.yml']],
            'another run' => [['id' => 124]],
            'pull request build' => [['event' => 'pull_request']],
        ];
    }

    public function test_downloads_artifact_without_forwarding_token_to_blob_host(): void
    {
        Http::preventStrayRequests();
        Storage::fake('local');
        $downloadUrl = 'https://productionresultssa1.blob.core.windows.net/actions-results/release.zip?sig=signed-secret';
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/artifacts/456/zip' => Http::response('', 302, ['Location' => $downloadUrl]),
            $downloadUrl => Http::response('release-archive', 200, ['Content-Length' => '15']),
        ]);

        (new GitRepositoryClient)->downloadArtifact($this->settings(), 456, Storage::disk('local')->path('release.zip'));

        $this->assertSame('release-archive', Storage::disk('local')->get('release.zip'));
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->url() === $downloadUrl && ! $request->hasHeader('Authorization'));
    }

    #[DataProvider('unsafeArtifactLocations')]
    public function test_rejects_unsafe_artifact_redirects_without_fetching_target(string $location): void
    {
        Http::preventStrayRequests();
        Storage::fake('local');
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/artifacts/456/zip' => Http::response('', 302, ['Location' => $location]),
        ]);

        try {
            (new GitRepositoryClient)->downloadArtifact($this->settings(), 456, Storage::disk('local')->path('release.zip'));
            $this->fail('An unsafe artifact host was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('unrecognized artifact download location', $exception->getMessage());
        }

        Storage::disk('local')->assertMissing('release.zip');
        Http::assertSentCount(1);
    }

    public static function unsafeArtifactLocations(): array
    {
        return [
            'local address' => ['https://127.0.0.1/private'],
            'plain HTTP' => ['http://productionresultssa1.blob.core.windows.net/release.zip'],
            'lookalike host' => ['https://productionresultssa1.blob.core.windows.net.evil.example/release.zip'],
            'credentials' => ['https://user:secret@productionresultssa1.blob.core.windows.net/release.zip'],
            'nonstandard port' => ['https://productionresultssa1.blob.core.windows.net:8443/release.zip'],
            'relative redirect' => ['/release.zip'],
        ];
    }

    public function test_rejects_oversized_artifact_before_writing_archive(): void
    {
        Http::preventStrayRequests();
        Storage::fake('local');
        $downloadUrl = 'https://productionresultssa1.blob.core.windows.net/release.zip';
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/artifacts/456/zip' => Http::response('', 302, ['Location' => $downloadUrl]),
            $downloadUrl => Http::response('too large', 200, ['Content-Length' => '524288001']),
        ]);

        try {
            (new GitRepositoryClient)->downloadArtifact($this->settings(), 456, Storage::disk('local')->path('release.zip'));
            $this->fail('The oversized archive was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('500 MB', $exception->getMessage());
        }

        Storage::disk('local')->assertMissing('release.zip');
        Http::assertSentCount(2);
    }

    public function test_removes_partial_artifact_after_incomplete_download(): void
    {
        Http::preventStrayRequests();
        Storage::fake('local');
        $downloadUrl = 'https://productionresultssa1.blob.core.windows.net/release.zip';
        Http::fake([
            'https://api.github.com/repos/company/portfolio/actions/artifacts/456/zip' => Http::response('', 302, ['Location' => $downloadUrl]),
            $downloadUrl => Http::response('partial', 200, ['Content-Length' => '20']),
        ]);

        try {
            (new GitRepositoryClient)->downloadArtifact($this->settings(), 456, Storage::disk('local')->path('release.zip'));
            $this->fail('The incomplete archive was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('incomplete release archive', $exception->getMessage());
        }

        Storage::disk('local')->assertMissing('release.zip');
        Http::assertSentCount(2);
    }

    public function test_does_not_overwrite_existing_local_archive(): void
    {
        Http::preventStrayRequests();
        Storage::fake('local');
        Storage::disk('local')->put('release.zip', 'existing-archive');

        try {
            (new GitRepositoryClient)->downloadArtifact($this->settings(), 456, Storage::disk('local')->path('release.zip'));
            $this->fail('An existing archive was overwritten.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('new file', $exception->getMessage());
        }

        $this->assertSame('existing-archive', Storage::disk('local')->get('release.zip'));
        Http::assertNothingSent();
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array{repository: string, branch: string, token: ?string, php_version: string, delivery: string}
     */
    private function settings(array $changes = []): array
    {
        return array_replace([
            'repository' => 'company/portfolio',
            'branch' => 'main',
            'token' => 'github_pat_test-secret',
            'php_version' => '8.4',
            'delivery' => 'zip',
        ], $changes);
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function workflowRun(array $changes = []): array
    {
        return array_replace([
            'id' => 123,
            'repository' => ['full_name' => 'company/portfolio'],
            'path' => '.github/workflows/deploy-release.yml',
            'event' => 'workflow_dispatch',
            'head_sha' => self::COMMIT,
            'status' => 'completed',
            'conclusion' => 'success',
        ], $changes);
    }
}
