<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\HtmlSanitizer;
use Illuminate\Support\Str;

/**
 * Builds `[locale => attributes]` translation payloads for CMS forms.
 */
trait BuildsTranslationPayload
{
    /**
     * @param  array<string, mixed>  $data
     * @param  callable(array<string, mixed>): array<string, mixed>  $mapTranslation
     * @return array<string, array<string, mixed>>
     */
    protected function buildTranslationPayload(
        array $data,
        callable $mapTranslation,
        string $titleField = 'title',
    ): array {
        return collect($data['translations'] ?? [])
            ->filter(fn (array $translation) => filled($translation[$titleField] ?? null))
            ->map(fn (array $translation) => $mapTranslation($translation))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $translation
     * @return array{title: mixed, slug: string, seo_title: mixed, seo_description: mixed}
     */
    protected function baseTranslationFields(array $translation, HtmlSanitizer $sanitizer): array
    {
        return [
            'title' => $translation['title'],
            'slug' => $translation['slug'] ?? Str::tajikSlug($translation['title']),
            'seo_title' => $translation['seo_title'] ?? null,
            'seo_description' => $translation['seo_description'] ?? null,
        ];
    }

    protected function sanitizedHtml(?string $html, HtmlSanitizer $sanitizer): ?string
    {
        return $sanitizer->clean($html);
    }
}
