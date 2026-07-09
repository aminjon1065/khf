<?php

namespace App\Models\Concerns;

use App\Services\Cms\PublishedContentCache;
use Spatie\ResponseCache\Facades\ResponseCache;

trait ClearsResponseCache
{
    /**
     * Boot the trait to clear the response cache on save and delete.
     *
     * @return void
     */
    public static function bootClearsResponseCache()
    {
        static::saved(function ($model) {
            ResponseCache::clear();
            app(PublishedContentCache::class)->bumpForModel($model);
        });

        static::deleted(function ($model) {
            ResponseCache::clear();
            app(PublishedContentCache::class)->bumpForModel($model);
        });
    }
}
