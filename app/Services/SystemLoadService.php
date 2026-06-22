<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class SystemLoadService
{
    private const CACHE_KEY = 'system:high_load_mode';

    /**
     * Check if the system is currently under emergency high load.
     * When true, expensive queries and features should be gracefully degraded.
     */
    public static function isHighLoad(): bool
    {
        return Cache::get(self::CACHE_KEY, false);
    }

    /**
     * Enable high load mode.
     */
    public static function enable(): void
    {
        // Store forever until manually disabled
        Cache::forever(self::CACHE_KEY, true);
    }

    /**
     * Disable high load mode.
     */
    public static function disable(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
