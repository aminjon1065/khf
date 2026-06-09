<?php

namespace App\Http\Requests\Admin;

use App\Models\Post;

class UpdatePostRequest extends StorePostRequest
{
    /**
     * Exclude the post being edited from the per-locale slug uniqueness check.
     */
    protected function currentPostId(): ?int
    {
        $post = $this->route('post');

        if ($post instanceof Post) {
            return $post->id;
        }

        return is_numeric($post) ? (int) $post : null;
    }
}
