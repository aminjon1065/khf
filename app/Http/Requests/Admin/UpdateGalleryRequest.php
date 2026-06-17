<?php

namespace App\Http\Requests\Admin;

use App\Models\Gallery;

class UpdateGalleryRequest extends StoreGalleryRequest
{
    /**
     * Exclude the gallery being edited from the per-locale slug uniqueness check.
     */
    protected function currentGalleryId(): ?int
    {
        $gallery = $this->route('gallery');

        return $gallery instanceof Gallery ? $gallery->id : null;
    }
}
