<?php

namespace App\Enums;

/**
 * Lifecycle of an emergency event (ТЗ §7.4): active → under control → resolved. Reflected on the
 * map and in feeds.
 */
enum IncidentStatus: string
{
    case Active = 'active';
    case Controlled = 'controlled';
    case Resolved = 'resolved';

    /**
     * Russian label for the CMS / UI (§7.1).
     */
    public function label(): string
    {
        return __('enums.incident_status.'.$this->value);
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
