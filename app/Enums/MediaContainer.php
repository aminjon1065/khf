<?php

namespace App\Enums;

/**
 * Storage visibility for media library folders (public site vs admin-only assets).
 */
enum MediaContainer: string
{
    case Public = 'public';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Публичные',
            self::Private => 'Приватные',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $container): string => $container->value, self::cases());
    }
}
