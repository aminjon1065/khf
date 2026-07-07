<?php

namespace App\Enums;

enum SearchContentType: string
{
    case Post = 'post';
    case Page = 'page';
    case Guide = 'guide';
    case Document = 'document';
    case Vacancy = 'vacancy';
    case Tender = 'tender';
    case Leader = 'leader';
    case Subdivision = 'subdivision';
    case Gallery = 'gallery';
    case Faq = 'faq';
    case Statistic = 'statistic';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function tryFromRequest(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}
