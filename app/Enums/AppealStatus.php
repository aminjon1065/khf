<?php

namespace App\Enums;

/**
 * Processing status of a citizen appeal / tourist-group application (ТЗ §6.7, §7.6).
 */
enum AppealStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return __('enums.appeal_status.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status): array => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }
}
