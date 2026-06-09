<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Seed the top-level administrative-territorial units of Tajikistan (ТЗ §6.3): Dushanbe and the
     * three provinces. Idempotent. Districts can be added later under each via `parent_id`.
     */
    public function run(): void
    {
        $regions = [
            ['code' => 'DUSHANBE', 'lat' => 38.5598, 'lng' => 68.7870, 'sort' => 1, 'names' => ['tj' => 'Душанбе', 'ru' => 'Душанбе', 'en' => 'Dushanbe']],
            ['code' => 'SUGHD', 'lat' => 40.2833, 'lng' => 69.6228, 'sort' => 2, 'names' => ['tj' => 'Вилояти Суғд', 'ru' => 'Согдийская область', 'en' => 'Sughd Region']],
            ['code' => 'KHATLON', 'lat' => 37.9000, 'lng' => 69.1000, 'sort' => 3, 'names' => ['tj' => 'Вилояти Хатлон', 'ru' => 'Хатлонская область', 'en' => 'Khatlon Region']],
            ['code' => 'GBAO', 'lat' => 37.5000, 'lng' => 71.6000, 'sort' => 4, 'names' => ['tj' => 'ВМКБ', 'ru' => 'ГБАО', 'en' => 'GBAO']],
        ];

        foreach ($regions as $data) {
            $region = Region::updateOrCreate(
                ['code' => $data['code']],
                ['parent_id' => null, 'latitude' => $data['lat'], 'longitude' => $data['lng'], 'sort_order' => $data['sort']],
            );

            $region->upsertTranslations(
                collect($data['names'])->map(fn (string $name): array => ['name' => $name])->all(),
            );
        }
    }
}
