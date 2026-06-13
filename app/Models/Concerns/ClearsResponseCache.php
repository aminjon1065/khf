<?php

namespace App\Models\Concerns;

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
        });

        static::deleted(function ($model) {
            ResponseCache::clear();
        });
    }
}
