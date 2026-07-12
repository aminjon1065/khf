<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed reference data, staff accounts, then full test content for local/staging CMS QA.
     */
    public function run(): void
    {
        $this->call([
            LanguageSeeder::class,
            GlobalSeeder::class,
            RolePermissionSeeder::class,
            RegionSeeder::class,
            AdminUserSeeder::class,
            TestContentSeeder::class,
            MenuSeeder::class,
        ]);
    }
}
