<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

/**
 * Loads the verbatim data harvested from the legacy khf.tj / kchs.tj sites, stored under
 * database/data/legacy/*.json. Shared by the Real* production seeders (WP-4).
 */
trait ReadsLegacyData
{
    /**
     * @return array<string, mixed>
     */
    protected function legacyData(string $file): array
    {
        $path = database_path('data/legacy/'.$file);

        if (! is_file($path)) {
            throw new RuntimeException("Legacy data file not found: {$path}");
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Legacy data file is not valid JSON: {$path}");
        }

        return $decoded;
    }

    /**
     * Keep only the locales that actually carry content — never fabricate or machine-translate a
     * missing locale (English is absent on the legacy sites and must stay null until supplied).
     *
     * @param  array<string, array<string, mixed>|null>  $translations
     * @param  callable(string, array<string, mixed>): array<string, mixed>  $map
     * @return array<string, array<string, mixed>>
     */
    protected function presentTranslations(array $translations, callable $map): array
    {
        $result = [];

        foreach (['tj', 'ru', 'en'] as $locale) {
            $payload = $translations[$locale] ?? null;

            if (is_array($payload) && $this->hasContent($payload)) {
                $result[$locale] = $map($locale, $payload);
            }
        }

        return $result;
    }

    /**
     * Clip a value to a varchar column's length (multibyte-safe), preserving null.
     */
    protected function clip(?string $value, int $length = 255): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return mb_strlen($value) > $length ? mb_substr($value, 0, $length) : $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasContent(array $payload): bool
    {
        foreach ($payload as $value) {
            if (is_string($value) && trim($value) !== '') {
                return true;
            }
        }

        return false;
    }
}
