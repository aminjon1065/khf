<?php

namespace App\Enums;

/**
 * Category of a public-procurement tender (ТЗ §9, §20 подпункт «э» — «торговая площадка»).
 */
enum TenderType: string
{
    case Goods = 'goods';
    case Works = 'works';
    case Services = 'services';
    case Consulting = 'consulting';

    /**
     * Russian label for the CMS (§7.1).
     */
    public function label(): string
    {
        return __('enums.tender_type.'.$this->value);
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
