<?php

namespace App\Support;

class MapTiles
{
    /**
     * @return array{tileUrl: string, attribution: string, tileSize: int, glyphsUrl: string}
     */
    public static function inertiaProps(): array
    {
        return [
            'tileUrl' => (string) config('map.tiles.url'),
            'attribution' => (string) config('map.tiles.attribution'),
            'tileSize' => (int) config('map.tiles.tile_size', 256),
            'glyphsUrl' => (string) config('map.tiles.glyphs'),
        ];
    }

    /**
     * Origins that MapLibre must be allowed to fetch (tiles + glyphs) under CSP connect-src.
     *
     * @return list<string>
     */
    public static function cspConnectSources(): array
    {
        $sources = [];

        foreach ([(string) config('map.tiles.url'), (string) config('map.tiles.glyphs')] as $template) {
            foreach (self::originsFromUrlTemplate($template) as $origin) {
                $sources[] = $origin;
            }
        }

        return array_values(array_unique($sources));
    }

    /**
     * @return list<string>
     */
    private static function originsFromUrlTemplate(string $template): array
    {
        if ($template === '') {
            return [];
        }

        $normalized = preg_replace('/\{[^}]+\}/', '0', $template) ?? $template;
        $parts = parse_url($normalized);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return [];
        }

        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $origins = ["{$scheme}://{$host}"];

        // OSM-style CDNs often serve tiles from a.tile… / b.tile… — allow the wildcard sibling.
        if (substr_count($host, '.') >= 2) {
            $labels = explode('.', $host);
            array_shift($labels);
            $origins[] = "{$scheme}://*.".implode('.', $labels);
        }

        return $origins;
    }
}
