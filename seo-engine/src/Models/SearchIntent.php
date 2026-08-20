<?php
declare(strict_types=1);

namespace SeoEngine\Models;

abstract class SearchIntent
{
    public const Informational          = 'اطلاعاتی';
    public const CommercialInvestigation = 'بررسی تجاری';
    public const Transactional          = 'تراکنشی';
    public const Navigational           = 'ناوبری';
    public const Local                  = 'محلی';
    public const Product                = 'محصول';
    public const Category               = 'دسته‌بندی';

    public static function all(): array
    {
        return [
            self::Informational,
            self::CommercialInvestigation,
            self::Transactional,
            self::Navigational,
            self::Local,
            self::Product,
            self::Category,
        ];
    }

    public static function isRevenue(string $intent): bool
    {
        return in_array($intent, [self::Transactional, self::Product], true);
    }

    public static function isCommercial(string $intent): bool
    {
        return in_array($intent, [
            self::Transactional,
            self::Product,
            self::CommercialInvestigation,
            self::Local,
        ], true);
    }
}
