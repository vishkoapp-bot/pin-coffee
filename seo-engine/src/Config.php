<?php
declare(strict_types=1);

namespace SeoEngine;

final class Config
{
    public const APP_NAME    = 'SEO Keyword Engine';
    public const APP_VERSION = '3.0.0';
    public const MAX_KEYWORDS = 100000;

    public const DIFFICULTY_NUMERIC = [
        'آسان'  => 20,
        'متوسط' => 50,
        'سخت'   => 80,
    ];

    public const CTR_BY_DIFFICULTY = [
        'آسان'  => 0.32,
        'متوسط' => 0.14,
        'سخت'   => 0.05,
    ];

    public const CONVERSION_RATES = [
        'تراکنشی'      => 0.048,
        'محصول'         => 0.038,
        'محلی'          => 0.032,
        'بررسی تجاری'   => 0.022,
        'دسته‌بندی'     => 0.015,
        'ناوبری'        => 0.008,
        'اطلاعاتی'      => 0.005,
    ];

    public const AVG_ORDER_VALUE = 450000;
}
