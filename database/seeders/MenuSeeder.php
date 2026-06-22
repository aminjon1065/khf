<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Menu::firstOrCreate(['location' => 'primary'], ['name' => 'Primary Navigation', 'is_active' => true]);
        Menu::firstOrCreate(['location' => 'footer'], ['name' => 'Footer Navigation', 'is_active' => true]);
    }
}
