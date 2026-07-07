<?php

namespace App\Http\Controllers\Concerns;

use App\Models\MediaFile;
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
        if ($request->hasFile('cover')) {
            $model->addMediaFromRequest('cover')->toMediaCollection($collection);
        } elseif ($request->filled('cover_media_id')) {
            $this->copyCoverFromLibrary($model, $collection, $request->integer('cover_media_id'));
        } elseif ($request->boolean('remove_cover')) {
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
