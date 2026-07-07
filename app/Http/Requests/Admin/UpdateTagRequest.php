<?php

namespace App\Http\Requests\Admin;

use App\Models\Tag;

class UpdateTagRequest extends StoreTagRequest
{
    protected function currentTagId(): ?int
    {
        $tag = $this->route('tag');

        if ($tag instanceof Tag) {
            return $tag->id;
        }

        return null;
    }
}
