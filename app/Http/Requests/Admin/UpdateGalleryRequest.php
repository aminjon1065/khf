<?php

namespace App\Http\Requests\Admin;

use App\Models\Gallery;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateGalleryRequest extends StoreGalleryRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $gallery = $this->route('gallery');
        $current = $gallery instanceof Gallery ? $gallery->status : null;

        return array_merge(
            $this->blueprintRules(),
            $this->statusTransitionRules($current),
        );
    }

    protected function currentGalleryId(): ?int
    {
        $gallery = $this->route('gallery');

        if ($gallery instanceof Gallery) {
            return $gallery->id;
        }

        return is_numeric($gallery) ? (int) $gallery : null;
    }
}
