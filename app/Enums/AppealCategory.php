<?php

namespace App\Enums;

/**
 * Citizen appeal topic classifier (ТЗ §6.7).
 */
enum AppealCategory: string
{
    case General = 'general';
    case Complaint = 'complaint';
    case Proposal = 'proposal';
    case Gratitude = 'gratitude';

    public function label(): string
    {
        return __('enums.appeal_category.'.$this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $category): string => $category->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $category): array => [
            'value' => $category->value,
            'label' => $category->label(),
        ], self::cases());
    }
}
