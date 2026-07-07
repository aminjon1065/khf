<?php

namespace App\Http\Requests\Admin;

use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdatePostRequest extends StorePostRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['status']);

        $post = $this->route('post');
        $current = $post instanceof Post ? $post->status : null;

        return array_merge($rules, $this->statusTransitionRules($current));
    }

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
