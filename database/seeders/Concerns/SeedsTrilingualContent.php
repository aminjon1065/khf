<?php

namespace Database\Seeders\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/**
 * Shared helpers for idempotent test/demo content seeders.
 */
trait SeedsTrilingualContent
{
    private const PLACEHOLDER_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private const MINI_PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

    /**
     * @param  array{tj: array<string, mixed>, ru: array<string, mixed>, en: array<string, mixed>}  $byLocale
     * @param  callable(string, array<string, mixed>): array<string, mixed>  $map
     * @return array<string, array<string, mixed>>
     */
    protected function trilingual(array $byLocale, callable $map): array
    {
        return collect(['tj', 'ru', 'en'])
            ->mapWithKeys(fn (string $locale): array => [
                $locale => $map($locale, $byLocale[$locale]),
            ])
            ->all();
    }

    protected function slugFor(string $englishTitle, string $locale, ?string $suffix = null): string
    {
        $base = Str::slug($englishTitle);

        if ($base === '') {
            $base = 'item';
        }

        return $base.($suffix ? '-'.$suffix : '').'-'.$locale;
    }

    /**
     * @param  HasMedia&Model  $model
     */
    protected function attachPublicImage(
        Model $model,
        string $collection,
        string $filename = 'pwa-512.png',
        bool $preserveOriginal = true,
    ): void {
        $path = public_path('images/'.$filename);

        if (is_file($path)) {
            $adder = $model->addMedia($path);

            if ($preserveOriginal) {
                $adder = $adder->preservingOriginal();
            }

            $adder->toMediaCollection($collection);

            return;
        }

        $model->addMediaFromString(base64_decode(self::PLACEHOLDER_PNG))
            ->usingFileName(str_replace('.webp', '.png', $filename))
            ->toMediaCollection($collection);
    }

    /**
     * @param  HasMedia&Model  $model
     */
    protected function attachPdf(Model $model, string $collection, string $filename): void
    {
        $model->addMediaFromString(self::MINI_PDF)
            ->usingFileName($filename)
            ->toMediaCollection($collection);
    }
}
