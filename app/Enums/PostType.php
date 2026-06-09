<?php

namespace App\Enums;

/**
 * Type of an operational/news material (ТЗ §6.2).
 */
enum PostType: string
{
    case News = 'news';
    case PressRelease = 'press_release';
    case Announcement = 'announcement';
    case Summary = 'summary';

    /**
     * Russian label for the CMS (§7.1).
     */
    public function label(): string
    {
        return match ($this) {
            self::News => 'Новость',
            self::PressRelease => 'Пресс-релиз',
            self::Announcement => 'Объявление',
            self::Summary => 'Оперативная сводка',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }
}
