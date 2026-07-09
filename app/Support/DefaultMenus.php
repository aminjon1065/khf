<?php

namespace App\Support;

use App\Models\Menu;

/**
 * Fixed public navigation slots (ТЗ §7.8): header and footer. Menus are provisioned automatically
 * so the CMS can focus on editing items rather than creating arbitrary menu records.
 */
class DefaultMenus
{
    /**
     * @return array<string, array{name: string}>
     */
    public static function definitions(): array
    {
        return [
            'primary' => ['name' => 'Главное меню'],
            'footer' => ['name' => 'Меню в подвале'],
        ];
    }

    public static function locationLabel(string $location): string
    {
        return match ($location) {
            'primary' => 'Шапка сайта',
            'footer' => 'Подвал сайта',
            default => $location,
        };
    }

    public static function ensure(): void
    {
        foreach (self::definitions() as $location => $attributes) {
            Menu::firstOrCreate(
                ['location' => $location],
                ['name' => $attributes['name'], 'is_active' => true],
            );
        }
    }
}
