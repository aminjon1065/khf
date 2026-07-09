<?php

namespace App\Support;

use App\Models\Language;
use App\Models\MediaFile;
use App\Services\Media\MediaUsageService;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaFilePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(MediaFile $mediaFile, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $media = $mediaFile->getFirstMedia('default');
        $translation = $mediaFile->translation($locale);

        /** @var array<string, array{alt_text: string|null}> $translations */
        $translations = [];

        foreach ($mediaFile->translations as $row) {
            $translations[$row->locale] = [
                'alt_text' => $row->alt_text,
            ];
        }

        return [
            'id' => $mediaFile->id,
            'name' => $mediaFile->name,
            'alt_text' => $translation?->alt_text ?? $mediaFile->alt_text,
            'translations' => $translations,
            'tags' => $mediaFile->tags->pluck('name')->values()->all(),
            'usages' => app(MediaUsageService::class)->presentFor($mediaFile),
            'media_folder_id' => $mediaFile->media_folder_id,
            'focal_x' => (float) ($mediaFile->focal_x ?? 50),
            'focal_y' => (float) ($mediaFile->focal_y ?? 50),
            'created_at' => $mediaFile->created_at?->toDateTimeString(),
            'mime_type' => $media?->mime_type,
            'is_image' => self::isImage($media),
            'original_url' => $media?->getUrl(),
            'thumb_url' => self::thumbUrl($media),
            'human_size' => $media?->human_readable_size,
            'media' => $media !== null
                ? [
                    [
                        'id' => $media->id,
                        'original_url' => $media->getUrl(),
                        'name' => $media->name,
                    ],
                ]
                : [],
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, MediaFile>  $paginator
     * @return array<string, mixed>
     */
    public static function paginate(LengthAwarePaginator $paginator): array
    {
        $locale = app()->getLocale();

        return [
            'data' => $paginator->getCollection()
                ->map(fn (MediaFile $mediaFile) => self::toArray($mediaFile, $locale))
                ->values()
                ->all(),
            'links' => $paginator->linkCollection()->toArray(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * @return list<array{code: string, native_name: string}>
     */
    public static function localeOptions(): array
    {
        return Language::active()
            ->map(fn (Language $language) => [
                'code' => $language->code,
                'native_name' => $language->native_name,
            ])
            ->all();
    }

    private static function isImage(?Media $media): bool
    {
        if ($media === null) {
            return false;
        }

        return str_starts_with($media->mime_type ?? '', 'image/');
    }

    private static function thumbUrl(?Media $media): ?string
    {
        if ($media === null) {
            return null;
        }

        if ($media->hasGeneratedConversion('thumb')) {
            return $media->getUrl('thumb');
        }

        return $media->getUrl();
    }
}
