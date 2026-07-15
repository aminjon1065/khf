<?php

namespace App\Console\Commands;

use App\Services\Public\SharedPublicProps;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Keep the emergency-alert banner truthful across scheduled time-window transitions (ТЗ §6.4.1).
 *
 * Publishing or cancelling an alert fires a model event that already invalidates both cache layers.
 * A *scheduled* `starts_at`/`ends_at` boundary fires no event, so without this the banner would keep
 * serving a stale "no alert" (or a stale active alert) for up to the cache TTL. Run every minute by
 * the shared-hosting cron (D-10): it compares the active-alert signature to the last run and, only
 * when it changed, bumps the shared-props version and clears the full-page response cache.
 */
#[Signature('alerts:refresh-cache')]
#[Description('Invalidate the alert banner caches when a scheduled alert window opens or closes')]
class RefreshAlertCache extends Command
{
    private const SIGNATURE_KEY = 'alerts.active.signature';

    public function handle(SharedPublicProps $shared): int
    {
        $signature = $shared->activeSignature();
        $previous = Cache::get(self::SIGNATURE_KEY);

        if ($signature === $previous) {
            return self::SUCCESS;
        }

        Cache::forever(self::SIGNATURE_KEY, $signature);
        $shared->bump(SharedPublicProps::GROUP_ALERTS);
        ResponseCache::clear();

        $this->info("Alert banner cache refreshed (active set: [{$signature}]).");

        return self::SUCCESS;
    }
}
