<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Seed the three portal languages (ТЗ §14): Tajik (default) and Russian are mandatory at
     * launch, English is for partners. `code` is the internal locale; `hreflang` is the valid
     * BCP-47 tag for SEO (Tajik internal `tj` → `tg`, see decision D-14). Idempotent.
     */
    public function run(): void
    {
        $languages = [
            ['code' => 'tj', 'name' => 'Tajik', 'native_name' => 'Тоҷикӣ', 'hreflang' => 'tg', 'is_default' => true, 'sort_order' => 1],
            ['code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский', 'hreflang' => 'ru', 'is_default' => false, 'sort_order' => 2],
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'hreflang' => 'en', 'is_default' => false, 'sort_order' => 3],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                [...$language, 'direction' => 'ltr', 'is_active' => true],
            );
        }
    }
}
