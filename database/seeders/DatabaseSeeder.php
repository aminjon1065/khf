<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database: reference data (languages, roles/permissions, regions),
     * staff accounts, then demo content for local/staging.
     */
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            RolePermissionSeeder::class,
            RegionSeeder::class,
            AdminUserSeeder::class,
            DemoContentSeeder::class,
        ]);
    }
}
