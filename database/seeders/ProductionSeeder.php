<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Real launch dataset: reference data + staff accounts + the actual content harvested from the
 * legacy khf.tj/kchs.tj sites (leadership, structure, legal acts, news, social channels) + menus.
 *
 * This is the entrypoint for a real deployment — `php artisan db:seed --class=ProductionSeeder`.
 * `DatabaseSeeder` is kept separate as the demo/QA fixture that fills every module for CMS testing.
 * Idempotent: each Real* seeder no-ops if its table already has rows.
 */
class ProductionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // Reference data.
            LanguageSeeder::class,
            GlobalSeeder::class,
            RolePermissionSeeder::class,
            RegionSeeder::class,
            AdminUserSeeder::class,

            // Real content harvested from khf.tj / kchs.tj (verbatim tj/ru; no fabricated en).
            RealLeadershipSeeder::class,
            RealStructureSeeder::class,
            RealDocumentSeeder::class,
            RealNewsSeeder::class,
            RealContactsSeeder::class,

            // Navigation.
            MenuSeeder::class,
        ]);
    }
}
