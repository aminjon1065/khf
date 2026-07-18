<?php

namespace App\Support;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Post-deploy smoke checks for staging/production UAT (ТЗ §18.1).
 *
 * In-process mode exercises the Laravel kernel (CI / local).
 * HTTP mode hits a live base URL (TLS, vhost, PHP-FPM on staging).
 */
class StagingSmokeChecker
{
    /**
     * @param  list<string>  $locales
     * @return list<array{path: string, ok: bool, status: int|null, message: string}>
     */
    public function run(bool $inProcess = true, ?string $baseUrl = null, array $locales = [], bool $insecure = false): array
    {
        $locales = $locales !== [] ? $locales : config('app.locales', ['tj', 'ru', 'en']);
        $results = [];

        $results[] = $this->staticPublicFile('robots.txt');

        foreach ($this->absolutePaths() as $check) {
            $results[] = $this->probe($check['path'], $check, $inProcess, $baseUrl, $insecure);
        }

        foreach ($locales as $locale) {
            foreach (config('deployment.smoke.locale_paths', []) as $suffix) {
                $path = '/'.$locale.($suffix === '/' ? '' : $suffix);
                $results[] = $this->probe($path, [
                    'path' => $path,
                    'expect' => [200],
                    'require_csrf' => $suffix === '/',
                    'require_csp' => $suffix === '/',
                ], $inProcess, $baseUrl, $insecure);
            }
        }

        $token = (string) config('deployment.health_check_token', '');

        if ($token !== '') {
            $results[] = $this->probe('/health', [
                'path' => '/health (authenticated)',
                'expect' => [200],
                'json_has' => 'checks',
                'request_headers' => ['Authorization' => 'Bearer '.$token],
            ], $inProcess, $baseUrl, $insecure);
        }

        return $results;
    }

    /**
     * @return array{path: string, ok: bool, status: int|null, message: string}
     */
    private function staticPublicFile(string $relative): array
    {
        $path = public_path($relative);
        $exists = is_file($path);

        return [
            'path' => '/'.$relative.' (public file)',
            'ok' => $exists,
            'status' => $exists ? 200 : null,
            'message' => $exists ? 'ok' : 'missing under public/',
        ];
    }

    /**
     * @return list<array{path: string, expect: list<int>, json_status?: string, require_csrf?: bool, require_csp?: bool}>
     */
    private function absolutePaths(): array
    {
        /** @var list<array{path: string, expect?: list<int>, json_status?: string, require_csrf?: bool, require_csp?: bool}> $configured */
        $configured = config('deployment.smoke.paths', []);

        return array_map(function (array $check): array {
            return [
                'path' => $check['path'],
                'expect' => $check['expect'] ?? [200],
                'json_status' => $check['json_status'] ?? null,
                'require_csrf' => $check['require_csrf'] ?? false,
                'require_csp' => $check['require_csp'] ?? false,
            ];
        }, $configured);
    }

    /**
     * @param  array{path: string, expect: list<int>, json_status?: string|null, require_csrf?: bool, require_csp?: bool, json_has?: string, request_headers?: array<string, string>}  $check
     * @return array{path: string, ok: bool, status: int|null, message: string}
     */
    private function probe(string $path, array $check, bool $inProcess, ?string $baseUrl, bool $insecure): array
    {
        $label = $check['path'];

        try {
            $requestHeaders = $check['request_headers'] ?? [];
            [$status, $body, $headers] = $inProcess
                ? $this->inProcess($path, $requestHeaders)
                : $this->viaHttp($path, $baseUrl, $insecure, $requestHeaders);

            if (! in_array($status, $check['expect'], true)) {
                return [
                    'path' => $label,
                    'ok' => false,
                    'status' => $status,
                    'message' => 'unexpected status (expected '.implode('|', $check['expect']).')',
                ];
            }

            if (($check['json_status'] ?? null) !== null) {
                $json = json_decode($body, true);

                if (! is_array($json) || ($json['status'] ?? null) !== $check['json_status']) {
                    return [
                        'path' => $label,
                        'ok' => false,
                        'status' => $status,
                        'message' => 'JSON status is not '.$check['json_status'],
                    ];
                }
            }

            if (($check['json_has'] ?? null) !== null) {
                $json = json_decode($body, true);

                if (! is_array($json) || ! array_key_exists($check['json_has'], $json)) {
                    return [
                        'path' => $label,
                        'ok' => false,
                        'status' => $status,
                        'message' => 'JSON missing key '.$check['json_has'],
                    ];
                }
            }

            if (($check['require_csrf'] ?? false) && ! str_contains($body, 'name="csrf-token"')) {
                return [
                    'path' => $label,
                    'ok' => false,
                    'status' => $status,
                    'message' => 'csrf-token meta missing',
                ];
            }

            if (($check['require_csp'] ?? false)) {
                $csp = $headers['content-security-policy'][0]
                    ?? $headers['Content-Security-Policy'][0]
                    ?? null;

                if (! is_string($csp) || $csp === '') {
                    return [
                        'path' => $label,
                        'ok' => false,
                        'status' => $status,
                        'message' => 'Content-Security-Policy header missing',
                    ];
                }
            }

            return [
                'path' => $label,
                'ok' => true,
                'status' => $status,
                'message' => 'ok',
            ];
        } catch (Throwable $e) {
            return [
                'path' => $label,
                'ok' => false,
                'status' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{0: int, 1: string, 2: array<string, list<string|null>>}
     */
    private function inProcess(string $path, array $requestHeaders = []): array
    {
        $kernel = app(HttpKernel::class);
        $request = Request::create($path, 'GET');

        foreach ($requestHeaders as $name => $value) {
            $request->headers->set($name, $value);
        }

        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return [
            $response->getStatusCode(),
            (string) $response->getContent(),
            $response->headers->all(),
        ];
    }

    /**
     * @return array{0: int, 1: string, 2: array<string, list<string|null>>}
     */
    private function viaHttp(string $path, ?string $baseUrl, bool $insecure, array $requestHeaders = []): array
    {
        $base = rtrim($baseUrl ?: (string) config('app.url'), '/');

        if ($base === '') {
            throw new \RuntimeException('APP_URL / --base-url is required for HTTP smoke checks.');
        }

        $request = Http::timeout((int) config('deployment.smoke.timeout', 15))
            ->withHeaders([
                'Accept' => 'text/html,application/json,*/*',
                ...$requestHeaders,
            ]);

        if ($insecure || $this->isLocalDevHost($base)) {
            $request = $request->withoutVerifying();
        }

        $response = $request->get($base.$path);

        /** @var array<string, list<string|null>> $headers */
        $headers = $response->headers();

        return [
            $response->status(),
            $response->body(),
            $headers,
        ];
    }

    private function isLocalDevHost(string $baseUrl): bool
    {
        $host = parse_url($baseUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        return $host === 'localhost'
            || $host === '127.0.0.1'
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.localhost');
    }

    /**
     * @param  list<array{path: string, ok: bool, status: int|null, message: string}>  $results
     */
    public function allPassed(array $results): bool
    {
        foreach ($results as $result) {
            if (! $result['ok']) {
                return false;
            }
        }

        return true;
    }
}
