<?php

namespace App\Support;

use App\Models\MediaFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaFilePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(MediaFile $mediaFile): array
    {
        $media = $mediaFile->getFirstMedia('default');

        return [
            'id' => $mediaFile->id,
            'name' => $mediaFile->name,
            'alt_text' => $mediaFile->alt_text,
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
        return [
            'data' => $paginator->getCollection()
                ->map(fn (MediaFile $mediaFile) => self::toArray($mediaFile))
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
