<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\KernelInterface;

class ViteAssetManifest
{
    private const DEV_SERVER_RETRY_INTERVAL = 1.0;

    private ?array $manifest = null;
    private ?bool $devServerAvailable = null;
    private ?string $resolvedDevServerUrl = null;
    private ?float $lastDevServerCheck = null;
    private ?bool $runningInsideDocker = null;

    public function __construct(
        private readonly KernelInterface $kernel,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%env(default::VITE_DEV_SERVER)%')]
        private readonly ?string $configuredDevServer = null,
    ) {
    }

    public function useDevServer(): bool
    {
        $this->resolveDevServer();

        return $this->devServerAvailable ?? false;
    }

    public function getDevServerUrl(): string
    {
        $this->resolveDevServer();

        if ($this->resolvedDevServerUrl !== null) {
            return $this->resolvedDevServerUrl;
        }

        $fallback = $this->resolvePublicUrlOverride('http://localhost:5173');

        return rtrim($this->configuredDevServer ?: $fallback, '/');
    }

    public function getEntry(string $entry): array
    {
        $manifest = $this->loadManifest();

        $normalized = ltrim($entry, '/');

        $candidates = [$normalized];

        if (!str_contains($normalized, '.')) {
            $candidates[] = sprintf('assets/%s', $normalized);

            foreach (['.js', '.jsx', '.ts', '.tsx'] as $extension) {
                $candidates[] = sprintf('assets/%s%s', $normalized, $extension);
            }
        } else {
            $candidates[] = sprintf('assets/%s', $normalized);
        }

        foreach ($candidates as $candidate) {
            if (isset($manifest[$candidate])) {
                return $manifest[$candidate];
            }
        }

        throw new \RuntimeException(sprintf('Entry "%s" not found in Vite manifest.', $entry));
    }

    public function resolveDevEntry(string $entry): string
    {
        $normalized = ltrim($entry, '/');

        if (str_contains($normalized, '.')) {
            return $normalized;
        }

        return sprintf('assets/%s.jsx', $normalized);
    }

    private function loadManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $manifestPath = sprintf('%s/public/build/.vite/manifest.json', $this->projectDir);

        if (!is_file($manifestPath)) {
            throw new \RuntimeException('Vite manifest not found. Run "npm run build" or start the dev server.');
        }

        $content = file_get_contents($manifestPath);

        if ($content === false) {
            throw new \RuntimeException('Unable to read Vite manifest.');
        }

        $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        $this->manifest = $decoded;

        return $this->manifest;
    }

    private function isDevServerReachable(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => 0.3,
            ],
        ]);

        try {
            return @file_get_contents(rtrim($url, '/') . '/@vite/client', false, $context) !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveDevServer(): void
    {
        if ($this->devServerAvailable === true) {
            return;
        }

        $now = microtime(true);

        if ($this->lastDevServerCheck !== null && ($now - $this->lastDevServerCheck) < self::DEV_SERVER_RETRY_INTERVAL) {
            return;
        }

        $this->lastDevServerCheck = $now;
        $this->devServerAvailable = false;
        $this->resolvedDevServerUrl = null;

        if ($this->kernel->getEnvironment() !== 'dev') {
            return;
        }

        if (!empty($this->configuredDevServer)) {
            $this->resolvedDevServerUrl = rtrim(
                $this->resolvePublicUrlOverride($this->configuredDevServer),
                '/'
            );
            $this->devServerAvailable = true;

            return;
        }

        foreach ($this->getDevServerCandidates() as $candidate) {
            if ($this->isDevServerReachable($candidate['ping'])) {
                $this->resolvedDevServerUrl = rtrim($candidate['public'], '/');
                $this->devServerAvailable = true;

                return;
            }
        }
    }

    /**
     * @return array<int, array{ping: string, public: string}>
     */
    private function getDevServerCandidates(): array
    {
        $publicUrl = 'http://localhost:5173';

        $candidates = [];

        $customPingUrl = $this->resolveEnvValue('VITE_DEV_SERVER_PING');

        if ($customPingUrl !== null) {
            $candidates[] = [
                'ping' => $customPingUrl,
                'public' => $this->resolvePublicUrlOverride($publicUrl),
            ];
        }

        if ($this->isRunningInsideDocker()) {
            foreach (['http://host.docker.internal:5173', 'http://172.17.0.1:5173'] as $dockerHostUrl) {
                $candidates[] = [
                    'ping' => $dockerHostUrl,
                    'public' => $this->resolvePublicUrlOverride($publicUrl),
                ];
            }
        }

        $candidates[] = [
            'ping' => 'http://127.0.0.1:5173',
            'public' => $this->resolvePublicUrlOverride($publicUrl),
        ];

        $candidates[] = [
            'ping' => $publicUrl,
            'public' => $this->resolvePublicUrlOverride($publicUrl),
        ];

        return $candidates;
    }

    private function resolveEnvValue(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? null;

        if ($value === null) {
            $fetched = getenv($name);
            $value = $fetched === false ? null : $fetched;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return rtrim($value, '/');
    }

    private function resolvePublicUrlOverride(string $default): string
    {
        $override = $this->resolveEnvValue('VITE_DEV_SERVER_PUBLIC_URL');

        if ($override !== null) {
            return $override;
        }

        return $default;
    }

    private function isRunningInsideDocker(): bool
    {
        if ($this->runningInsideDocker !== null) {
            return $this->runningInsideDocker;
        }

        if (is_file('/.dockerenv')) {
            $this->runningInsideDocker = true;

            return true;
        }

        $cgroupPath = '/proc/1/cgroup';

        if (is_readable($cgroupPath)) {
            try {
                $content = file_get_contents($cgroupPath);

                if ($content !== false && str_contains($content, 'docker')) {
                    $this->runningInsideDocker = true;

                    return true;
                }
            } catch (\Throwable) {
                // ignore read issues and fallback to default below
            }
        }

        $this->runningInsideDocker = false;

        return false;
    }
}
