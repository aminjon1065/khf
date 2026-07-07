<?php

namespace App\Support;

class SocialLinks
{
    /**
     * @return list<array{platform: string, url: string}>
     */
    public static function all(): array
    {
        return collect(config('social.links', []))
            ->filter(fn (mixed $url, string $platform) => self::isValidUrl($url))
            ->map(fn (mixed $url, string $platform): array => [
                'platform' => $platform,
                'url' => (string) $url,
            ])
            ->values()
            ->all();
    }

    private static function isValidUrl(mixed $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array($scheme, ['http', 'https'], true);
    }
}
