<?php

namespace App\Console\Commands;

use App\Services\Cms\PublishedContentCache;
use Illuminate\Console\Command;

class CmsCacheWarmCommand extends Command
{
    protected $signature = 'cms:cache-warm
                            {--locale= : Warm only a single locale (tj, ru, en)}';

    protected $description = 'Warm the Stache-like published content cache (slug indexes, globals, common fragments)';

    public function handle(PublishedContentCache $cache): int
    {
        $locale = $this->option('locale');

        if (is_string($locale) && $locale !== '' && ! in_array($locale, config('app.locales', []), true)) {
            $this->components->error("Unknown locale «{$locale}».");

            return self::FAILURE;
        }

        $this->components->info('Warming published content cache…');

        $stats = $cache->warm(is_string($locale) && $locale !== '' ? $locale : null);

        $this->components->twoColumnDetail('Slug index entries', (string) $stats['slug_indexes']);
        $this->components->twoColumnDetail('Locale fragments', (string) $stats['locale_fragments']);
        $this->components->twoColumnDetail('Global resolutions', (string) $stats['globals']);
        $this->components->success('Published content cache warmed.');

        return self::SUCCESS;
    }
}
