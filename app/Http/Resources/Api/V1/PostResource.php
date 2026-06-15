<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array. The full body is included only on the single-item
     * (show) endpoint to keep list payloads light.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Post $post */
        $post = $this->resource;
        $translation = $post->translation();
        $slug = $translation?->getAttribute('slug');

        return [
            'id' => $post->id,
            'type' => [
                'value' => $post->type->value,
                'label' => $post->type->label(),
            ],
            'title' => $translation?->getAttribute('title'),
            'slug' => $slug,
            'excerpt' => $translation?->getAttribute('excerpt'),
            'body' => $this->when(
                $request->routeIs('api.v1.news.show'),
                fn () => $translation?->getAttribute('body'),
            ),
            'url' => $slug ? route('news.show', ['locale' => app()->getLocale(), 'slug' => $slug]) : null,
            'category' => $post->category?->translation()?->getAttribute('name'),
            'published_at' => $post->published_at?->toIso8601String(),
        ];
    }
}
