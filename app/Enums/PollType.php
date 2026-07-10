<?php

namespace App\Enums;

/**
 * Poll category (ТЗ §8, §20 «к»). Anti-corruption expertise polls target draft legal acts.
 */
enum PollType: string
{
    case General = 'general';
    case AntiCorruptionExpertise = 'anti_corruption_expertise';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __('enums.poll_type.'.$this->value);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
