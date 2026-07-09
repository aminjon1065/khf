<?php

namespace App\Http\Controllers\Concerns;

use App\Models\MediaFile;
use App\Services\Media\MediaUsageService;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\HasMedia;

trait SyncsCoverFromLibrary
{
    /**
     * Set the cover from an upload or a picked media-library asset, or clear it when the "remove"
     * flag is set (ТЗ §6.2, §7.7).
     */
    protected function syncCover(Request $request, HasMedia $model, string $collection): void
    {
        $usage = app(MediaUsageService::class);

        if ($request->hasFile('cover')) {
            $usage->syncLibraryReference($model, null, $collection, '');
            $model->addMediaFromRequest('cover')->toMediaCollection($collection);

            return;
        }

        if ($request->filled('cover_media_id')) {
            $mediaFileId = $request->integer('cover_media_id');
            $this->copyCoverFromLibrary($model, $collection, $mediaFileId);
            $usage->syncLibraryReference(
                $model,
                $mediaFileId,
                $collection,
                $usage->labelForCover($model),
            );

            return;
        }

        if ($request->boolean('remove_cover')) {
            $usage->syncLibraryReference($model, null, $collection, '');
            $model->clearMediaCollection($collection);
        }
    }

    /**
     * Copy a media-library asset's file into the model's (single-file) cover collection.
     */
    protected function copyCoverFromLibrary(HasMedia $model, string $collection, int $mediaFileId): void
    {
        $media = MediaFile::find($mediaFileId)?->getFirstMedia('default');

        if ($media === null) {
            return;
        }

        $model->clearMediaCollection($collection);
        $media->copy($model, $collection);
    }
}
