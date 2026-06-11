<?php

namespace App\Enums;

/**
 * Intended audience of a safety guide (ТЗ §6.5). `Children` powers the educational / children
 * materials sub-section. Display labels come from interface translations (Phase 13).
 */
enum GuideAudience: string
{
    case General = 'general';
    case Children = 'children';

    public function label(): string
    {
        return __('enums.guide_audience.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $audience): string => $audience->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $audience): array => [
            'value' => $audience->value,
            'label' => $audience->label(),
        ], self::cases());
    }
}
