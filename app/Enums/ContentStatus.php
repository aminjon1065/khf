<?php

namespace App\Enums;

/**
 * Publication lifecycle for content entities (ТЗ §6.2, §7.2): draft → moderation → published →
 * archived. Shared by pages, posts, documents, etc.
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Moderation = 'moderation';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Russian label for the CMS (§7.1).
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Moderation => 'На модерации',
            self::Published => 'Опубликовано',
            self::Archived => 'В архиве',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
