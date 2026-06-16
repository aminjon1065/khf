<?php

namespace App\Enums;

/**
 * Employment type of a civil-service vacancy (ТЗ §20 подпункт «н»).
 */
enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Temporary = 'temporary';

    /**
     * Russian label for the CMS (§7.1).
     */
    public function label(): string
    {
        return __('enums.employment_type.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }
}
