<?php

namespace App\Enums;

/**
 * Document register categories (ТЗ §6.8).
 */
enum DocumentType: string
{
    case Law = 'law';
    case Regulation = 'regulation';
    case Departmental = 'departmental';
    case Plan = 'plan';
    case Report = 'report';
    case Form = 'form';

    /**
     * Russian label for the CMS / UI (§7.1).
     */
    public function label(): string
    {
        return __('enums.document_type.'.$this->value);
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
