<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PostResource;
use App\Models\Post;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Published news for the internal API (ТЗ §6.2, §10.9). Read-only; drafts and scheduled-but-future
 * posts are never exposed (the published scope is applied to both list and show).
 */
class PostController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $posts = Post::published()
            ->with(['translations', 'category.translations'])
            ->orderByDesc('published_at')
            ->paginate(15);

        return PostResource::collection($posts);
    }

    public function show(Post $post): PostResource
    {
        abort_unless(
            Post::published()->whereKey($post->getKey())->exists(),
            404,
        );

        $post->load(['translations', 'category.translations']);

        return new PostResource($post);
    }
}
