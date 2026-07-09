<?php

namespace App\Support;

use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RedirectResolver
{
    public const CACHE_KEY = 'cms.redirects.map';

    /**
     * @return array<string, array{to: string, status: int}>
     */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $map = self::configEntries();

            Redirect::query()
                ->where('is_active', true)
                ->get(['from_path', 'to_url', 'status_code'])
                ->each(function (Redirect $redirect) use (&$map): void {
                    self::putEntry($map, $redirect->from_path, $redirect->to_url, $redirect->status_code);
                });

            return $map;
        });
    }

    public static function normalizePath(string $path): string
    {
        $path = trim($path);

        if (str_contains($path, '://')) {
            $parsed = parse_url($path, PHP_URL_PATH);
            $path = is_string($parsed) ? $parsed : $path;
        }

        return ltrim($path, '/');
    }

    /**
     * @return array{to: string, status: int}|null
     */
    public static function match(Request $request): ?array
    {
        $map = self::map();
        $path = $request->path();

        foreach ([$path, self::normalizePath($path), '/'.self::normalizePath($path)] as $key) {
            if (isset($map[$key])) {
                return $map[$key];
            }
        }

        return null;
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, array{to: string, status: int}>  $map
     */
    private static function putEntry(array &$map, string $from, string $to, int $status): void
    {
        $normalized = self::normalizePath($from);
        $entry = ['to' => $to, 'status' => $status];

        $map[$normalized] = $entry;
        $map['/'.$normalized] = $entry;
    }

    /**
     * @return array<string, array{to: string, status: int}>
     */
    private static function configEntries(): array
    {
        $map = [];

        foreach (config('redirects', []) as $from => $to) {
            if (! is_string($from) || ! is_string($to)) {
                continue;
            }

            self::putEntry($map, $from, $to, 301);
        }

        return $map;
    }
}
