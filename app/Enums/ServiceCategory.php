<?php

namespace App\Enums;

/**
 * Category of a government service in the catalogue (ТЗ §20 «ф»).
 */
enum ServiceCategory: string
{
    case Registration = 'registration';
    case Certification = 'certification';
    case Information = 'information';
    case Consultation = 'consultation';
    case Appeals = 'appeals';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __('enums.service_category.'.$this->value);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category): array => ['value' => $category->value, 'label' => $category->label()],
            self::cases(),
        );
    }
}
