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
        return __('enums.content_status.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }

    /**
     * Allowed next statuses from the current one (ТЗ §7.2 workflow).
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Moderation, self::Published],
            self::Moderation => [self::Draft, self::Published],
            self::Published => [self::Archived],
            self::Archived => [self::Draft],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
