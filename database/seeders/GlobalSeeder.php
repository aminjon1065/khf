<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\SiteGlobal;
use Illuminate\Database\Seeder;

class GlobalSeeder extends Seeder
{
    /**
     * Seed CMS globals from config fallbacks.
     */
    public function run(): void
    {
        $locale = Language::defaultCode();

        foreach (config('cms.globals', []) as $handle => $config) {
            $global = SiteGlobal::query()->updateOrCreate(
                ['handle' => $handle],
                ['blueprint' => $config['blueprint']],
            );

            $fallback = $config['fallback'] ?? [];

            if ($fallback === []) {
                continue;
            }

            $global->upsertTranslations([
                $locale => ['data' => $fallback],
            ]);
        }
    }
}
