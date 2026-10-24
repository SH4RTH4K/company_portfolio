<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class GitRepositoryClient
{
    private const API_URL = 'https://api.github.com';

    private const WORKFLOW = 'deploy-release.yml';

    private const MAX_ARTIFACT_BYTES = 524288000;

    /**
     * @param  array{repository: string, branch: string, token?: ?string, php_version?: string, delivery?: string}  $settings
     * @return array{repository: string, private: bool, branch: string, commit: string, message: string, url: string}
     */
    public function inspect(array $settings): array
    {
        $repository = $this->repository($settings);
        $branch = $this->branch($settings);
        $metadata = $this->get($settings, '/repos/'.$repository)->json();

        if (! is_array($metadata) || strcasecmp((string) ($metadata['full_name'] ?? ''), $repository) !== 0) {
            throw new RuntimeException('GitHub returned an unexpected repository. Check the saved repository name.');
        }

        $commit = $this->get($settings, '/repos/'.$repository.'/commits/'.rawurlencode($branch))->json();
        $commitHash = is_array($commit) ? (string) ($commit['sha'] ?? '') : '';
        $this->validateCommit($commitHash);

        return [
            'repository' => $repository,
            'private' => (bool) ($metadata['private'] ?? false),
            'branch' => $branch,
            'commit' => strtolower($commitHash),
            'message' => mb_substr((string) data_get($commit, 'commit.message', ''), 0, 2000),
            'url' => 'https://github.com/'.$repository.'/commit/'.$commitHash,
        ];
    }

    /**
     * @param  array{repository: string, branch: string, token?: ?string, php_version: string, delivery: string, artifact_run_id?: int|string|null}  $settings
     * @return array{run_id: ?int, run_url: ?string}
     */
    public function dispatch(array $settings, string $commit): array
    {
        $repository = $this->repository($settings);
        $branch = $this->branch($settings);
        $this->validateCommit($commit);

        if ($this->token($settings) === null) {
            throw new RuntimeException('Save a GitHub token with Actions read and write permission before starting a build, including for public repositories.');
        }

        if (! in_array($settings['php_version'] ?? null, ['8.3', '8.4'], true)
            || ! in_array($settings['delivery'] ?? null, ['zip', 'ftps'], true)) {
            throw new RuntimeException('Choose PHP 8.3 or 8.4 and a ZIP or FTPS delivery method.');
        }

        $artifactRunId = $settings['artifact_run_id'] ?? '';

        if ($artifactRunId !== '' && ((! is_string($artifactRunId) && ! is_int($artifactRunId))
            || ! preg_match('/\A[0-9]+\z/', (string) $artifactRunId)
            || filter_var($artifactRunId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false)) {
            throw new RuntimeException('Choose a valid positive GitHub Actions build run ID for the approved release artifact.');
        }

        try {
            $response = $this->request($settings)->post(self::API_URL.'/repos/'.$repository.'/actions/workflows/'.self::WORKFLOW.'/dispatches', [
                'ref' => $branch,
                'return_run_details' => true,
                'inputs' => [
                    'commit' => strtolower($commit),
                    'php_version' => $settings['php_version'],
                    'delivery' => $settings['delivery'],
                    'artifact_run_id' => (string) $artifactRunId,
                ],
            ]);
        } catch (ConnectionException) {
            throw new RuntimeException('GitHub could not confirm the build request. Check GitHub Actions before trying again to avoid starting a duplicate deployment.');
        }

        $this->ensureStatus($response, [200, 204]);

        if ($response->status() === 204) {
            return ['run_id' => null, 'run_url' => null];
        }

        $runId = $response->json('workflow_run_id');

        if (! is_int($runId) || $runId < 1) {
            throw new RuntimeException('GitHub accepted the build request but did not identify its run. Check GitHub Actions before trying again.');
        }

        return [
            'run_id' => $runId,
            'run_url' => 'https://github.com/'.$repository.'/actions/runs/'.$runId,
        ];
    }

    /**
     * @param  array{repository: string, branch: string, token?: ?string, php_version?: string, delivery?: string}  $settings
     * @return array{run_id: int, status: string, conclusion: ?string, commit: string, name: string, url: string, artifacts: list<array{id: int, name: string, size: int}>}
     */
    public function run(array $settings, int $runId): array
    {
        $repository = $this->repository($settings);

        if ($runId < 1) {
            throw new RuntimeException('Choose a valid GitHub Actions run.');
        }

        $run = $this->get($settings, '/repos/'.$repository.'/actions/runs/'.$runId)->json();

        if (! is_array($run)
            || ($run['id'] ?? null) !== $runId
            || strcasecmp((string) data_get($run, 'repository.full_name', ''), $repository) !== 0
            || preg_replace('/@.*$/', '', (string) ($run['path'] ?? '')) !== '.github/workflows/'.self::WORKFLOW
            || ($run['event'] ?? null) !== 'workflow_dispatch') {
            throw new RuntimeException('This GitHub Actions run does not belong to the saved deployment workflow.');
        }

        $commit = (string) ($run['head_sha'] ?? '');
        $this->validateCommit($commit);
        $artifacts = [];

        if (($run['status'] ?? null) === 'completed' && ($run['conclusion'] ?? null) === 'success') {
            $response = $this->get($settings, '/repos/'.$repository.'/actions/runs/'.$runId.'/artifacts?per_page=100');

            foreach ((array) $response->json('artifacts', []) as $artifact) {
                if (! is_array($artifact)
                    || ($artifact['expired'] ?? true) !== false
                    || ! is_int($artifact['id'] ?? null)
                    || $artifact['id'] < 1
                    || ! is_string($artifact['name'] ?? null)
                    || ! preg_match('/\Adeployment-release-[a-f0-9]{40}\z/', $artifact['name'])
                    || ! is_int($artifact['size_in_bytes'] ?? null)
                    || $artifact['size_in_bytes'] < 1
                    || $artifact['size_in_bytes'] > self::MAX_ARTIFACT_BYTES) {
                    continue;
                }

                $artifacts[] = [
                    'id' => $artifact['id'],
                    'name' => $artifact['name'],
                    'size' => $artifact['size_in_bytes'],
                ];
            }
        }

        return [
            'run_id' => $runId,
            'status' => (string) ($run['status'] ?? 'unknown'),
            'conclusion' => isset($run['conclusion']) ? (string) $run['conclusion'] : null,
            'commit' => strtolower($commit),
            'name' => (string) ($run['name'] ?? ''),
            'url' => 'https://github.com/'.$repository.'/actions/runs/'.$runId,
            'artifacts' => $artifacts,
        ];
    }

    /**
     * @param  array{repository: string, branch: string, token?: ?string, php_version?: string, delivery?: string}  $settings
     */
    public function downloadArtifact(array $settings, int $artifactId, string $destination): void
    {
        $repository = $this->repository($settings);

        if ($artifactId < 1 || str_contains($destination, "\0") || ! is_dir(dirname($destination)) || file_exists($destination) || is_link($destination)) {
            throw new RuntimeException('The release download needs a new file in private local storage.');
        }

        try {
            $redirect = $this->request($settings)->get(self::API_URL.'/repos/'.$repository.'/actions/artifacts/'.$artifactId.'/zip');
        } catch (ConnectionException) {
            throw new RuntimeException('GitHub could not be reached. Check the hosting network connection and try again.');
        }

        $this->ensureStatus($redirect, [302]);
        $downloadUrl = $redirect->header('Location');
        $this->validateArtifactUrl($downloadUrl);
        $stream = null;
        $output = null;
        $completed = false;

        try {
            try {
                $response = Http::connectTimeout(10)->timeout(120)
                    ->withOptions(['allow_redirects' => false, 'stream' => true])
                    ->get($downloadUrl);
            } catch (Throwable) {
                throw new RuntimeException('The release download could not finish. Check the hosting network connection and try again.');
            }

            $this->ensureStatus($response, [200]);
            $stream = $response->toPsrResponse()->getBody();
            $contentLength = (int) $response->header('Content-Length');

            if ($contentLength > self::MAX_ARTIFACT_BYTES) {
                throw new RuntimeException('The release archive exceeds the 500 MB download limit.');
            }

            $output = @fopen($destination, 'xb');

            if ($output === false) {
                throw new RuntimeException('The release archive could not be saved to private local storage.');
            }

            @chmod($destination, 0600);
            $downloadedBytes = 0;

            while (! $stream->eof()) {
                try {
                    $chunk = $stream->read(1048576);
                } catch (Throwable) {
                    throw new RuntimeException('The release download stopped before it was complete. Try downloading again.');
                }

                $downloadedBytes += strlen($chunk);

                if ($downloadedBytes > self::MAX_ARTIFACT_BYTES) {
                    throw new RuntimeException('The release archive exceeds the 500 MB download limit.');
                }

                if ($chunk === '' && ! $stream->eof()) {
                    throw new RuntimeException('The release download stopped before it was complete. Try downloading again.');
                }

                if (@fwrite($output, $chunk) !== strlen($chunk)) {
                    throw new RuntimeException('The release archive could not be saved. Check available hosting disk space.');
                }
            }

            if ($downloadedBytes === 0 || ($contentLength > 0 && $downloadedBytes !== $contentLength)) {
                throw new RuntimeException('GitHub returned an incomplete release archive. Download the release again.');
            }

            $completed = true;
        } finally {
            $stream?->close();
            $createdFile = is_resource($output);

            if ($createdFile) {
                fclose($output);
            }

            if (! $completed && $createdFile && file_exists($destination)) {
                @unlink($destination);
            }
        }
    }

    /**
     * @param  array{repository: string, branch: string, token?: ?string, php_version?: string, delivery?: string}  $settings
     */
    private function request(array $settings): PendingRequest
    {
        $request = Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2026-03-10',
            'User-Agent' => 'CompanyPortfolioDeployment',
        ])->connectTimeout(5)->timeout(20)->withOptions(['allow_redirects' => false]);
        $token = $this->token($settings);

        if ($token !== null) {
            $request->withToken($token);
        }

        return $request;
    }

    /**
     * @param  array{repository: string, branch: string, token?: ?string, php_version?: string, delivery?: string}  $settings
     */
    private function get(array $settings, string $path): Response
    {
        try {
            $response = $this->request($settings)->get(self::API_URL.$path);
        } catch (ConnectionException) {
            throw new RuntimeException('GitHub could not be reached. Check the hosting network connection and try again.');
        }

        $this->ensureStatus($response, [200]);

        return $response;
    }

    /**
     * @param  list<int>  $statuses
     */
    private function ensureStatus(Response $response, array $statuses): void
    {
        if (in_array($response->status(), $statuses, true)) {
            return;
        }

        throw new RuntimeException(match ($response->status()) {
            401 => 'GitHub rejected the saved token. Replace an expired or revoked token and try again.',
            403 => $response->header('X-RateLimit-Remaining') === '0'
                ? 'The GitHub API rate limit was reached. Wait before trying again.'
                : 'GitHub denied access. Check the token repository permissions and any organization approval requirements.',
            404 => 'GitHub could not find the repository, branch, workflow, run, or artifact. For a private repository, also check token access.',
            410 => 'This GitHub release artifact has expired. Build a new release.',
            422 => 'GitHub could not start the workflow. Commit deploy-release.yml to the selected branch and check its inputs and permissions.',
            429 => 'The GitHub API rate limit was reached. Wait before trying again.',
            default => 'GitHub returned an unexpected response. Check GitHub Actions and try again later.',
        });
    }

    /**
     * @param  array{repository: string, branch: string, token?: ?string, php_version?: string, delivery?: string}  $settings
     */
    private function repository(array $settings): string
    {
        $repository = $settings['repository'] ?? null;

        if (! is_string($repository)
            || ! preg_match('/\A[A-Za-z0-9][A-Za-z0-9-]{0,38}\/[A-Za-z0-9_.-]{1,100}\z/', $repository)
            || in_array(explode('/', $repository)[1], ['.', '..'], true)) {
            throw new RuntimeException('Enter the GitHub repository as owner/repository, without a URL or credentials.');
        }

        return $repository;
    }

    /**
     * @param  array{repository: string, branch: string, token?: ?string, php_version?: string, delivery?: string}  $settings
     */
    private function branch(array $settings): string
    {
        $branch = $settings['branch'] ?? null;

        if (! is_string($branch)
            || strlen($branch) > 255
            || ! preg_match('/\A[A-Za-z0-9][A-Za-z0-9._\/-]*\z/', $branch)
            || str_contains($branch, '..')
            || str_contains($branch, '//')
            || str_ends_with($branch, '/')
            || str_ends_with($branch, '.')
            || preg_match('/(?:\A|\/)\.|\.lock(?:\/|\z)/', $branch)) {
            throw new RuntimeException('Enter a valid Git branch name.');
        }

        return $branch;
    }

    /**
     * @param  array{repository: string, branch: string, token?: ?string, php_version?: string, delivery?: string}  $settings
     */
    private function token(array $settings): ?string
    {
        $token = $settings['token'] ?? null;

        if ($token === null || $token === '') {
            return null;
        }

        if (! is_string($token) || strlen($token) > 1000 || ! preg_match('/\A[!-~]+\z/', $token)) {
            throw new RuntimeException('The saved GitHub token is invalid. Save a new token without spaces or line breaks.');
        }

        return $token;
    }

    private function validateCommit(string $commit): void
    {
        if (! preg_match('/\A[a-f0-9]{40}\z/i', $commit)) {
            throw new RuntimeException('GitHub must identify the release using its complete 40-character commit hash.');
        }
    }

    private function validateArtifactUrl(string $url): void
    {
        $parts = parse_url($url);
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';

        if (! is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || isset($parts['user'])
            || isset($parts['pass'])
            || (isset($parts['port']) && $parts['port'] !== 443)
            || (! preg_match('/\A[a-z0-9-]+\.blob\.core\.windows\.net\z/', $host)
                && ! preg_match('/\A[a-z0-9-]+\.actions\.githubusercontent\.com\z/', $host))) {
            throw new RuntimeException('GitHub returned an unrecognized artifact download location. Download the release from GitHub Actions instead.');
        }
    }
}
