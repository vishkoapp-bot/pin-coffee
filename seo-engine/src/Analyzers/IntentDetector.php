<?php
declare(strict_types=1);

namespace SeoEngine\Analyzers;

use SeoEngine\Models\FunnelStage;
use SeoEngine\Models\SearchIntent;
use SeoEngine\Services\TextNormalizer;

/**
 * Detects search intent using weighted signal matching.
 *
 * Each signal category has weighted patterns: exact match signals
 * receive higher weight, partial substring matches receive lower weight.
 * Word count heuristics provide secondary classification signals.
 */
final class IntentDetector
{
    private const TRANSACTIONAL_SIGNALS = [
        'خرید' => 3, 'قیمت' => 3, 'سفارش' => 3, 'ارزان' => 2,
        'فروش' => 2, 'تخفیف' => 3, 'بهترین قیمت' => 4, 'موجود' => 2,
        'اصل' => 1, 'ارسال' => 2, 'فوری' => 2, 'فروشگاه' => 2,
        'سبد خرید' => 4, 'پرداخت' => 3, 'ثبت سفارش' => 4,
    ];

    private const INFORMATIONAL_SIGNALS = [
        'چیست' => 3, 'چگونه' => 3, 'آموزش' => 3, 'راهنما' => 2,
        'نحوه' => 2, 'چرا' => 2, 'تفاوت' => 2, 'معرفی' => 2,
        'آیا' => 2, 'چه زمانی' => 3, 'چطور' => 3, 'روش' => 1,
        'مراحل' => 2, 'ترفند' => 2, 'نکات' => 2,
    ];

    private const COMMERCIAL_SIGNALS = [
        'بهترین' => 3, 'مقایسه' => 3, 'بررسی' => 2, 'نقد' => 2,
        'رتبه‌بندی' => 3, 'لیست' => 2, 'پیشنهاد' => 2, 'انتخاب' => 2,
        'راهنمای خرید' => 4, 'نقد و بررسی' => 4, 'تست' => 2,
        'مزایا و معایب' => 4, 'vs' => 2, 'در مقابل' => 3,
    ];

    private const LOCAL_SIGNALS = [
        'نزدیک' => 3, 'تهران' => 2, 'اصفهان' => 2, 'شیراز' => 2,
        'مشهد' => 2, 'تبریز' => 2, 'آدرس' => 3, 'نمایندگی' => 3,
        'شعبه' => 3, 'نقشه' => 2, 'منطقه' => 2,
    ];

    private const PRODUCT_SIGNALS = [
        'مدل' => 2, 'سایز' => 2, 'رنگ' => 1, 'برند' => 2,
        'جنس' => 1, 'نوع' => 1, 'مشخصات' => 3, 'اسپک' => 2,
    ];

    private const CATEGORY_SIGNALS = [
        'انواع' => 3, 'لیست' => 2, 'دسته' => 2, 'مجموعه' => 2,
        'کالکشن' => 2, 'همه' => 1, 'کامل' => 1,
    ];

    public static function detect(string $keyword): string
    {
        $lower = TextNormalizer::toLower($keyword);
        $wordCount = count(explode(' ', $keyword));

        $scores = [
            'transactional'  => self::matchSignals($lower, self::TRANSACTIONAL_SIGNALS),
            'informational'  => self::matchSignals($lower, self::INFORMATIONAL_SIGNALS),
            'commercial'     => self::matchSignals($lower, self::COMMERCIAL_SIGNALS),
            'local'          => self::matchSignals($lower, self::LOCAL_SIGNALS),
            'product'        => self::matchSignals($lower, self::PRODUCT_SIGNALS),
            'category'       => self::matchSignals($lower, self::CATEGORY_SIGNALS),
            'navigational'   => 0,
        ];

        if ($wordCount <= 2) {
            $scores['navigational'] += 2;
        }
        if ($wordCount >= 4) {
            $scores['informational'] += 1;
        }
        if ($wordCount >= 5) {
            $scores['informational'] += 1;
        }

        $maxScore = max($scores);
        if ($maxScore === 0) {
            return $wordCount <= 2 ? SearchIntent::Navigational : SearchIntent::Informational;
        }

        $maxType = array_keys($scores, $maxScore)[0];

        return match ($maxType) {
            'transactional' => SearchIntent::Transactional,
            'informational' => SearchIntent::Informational,
            'commercial'    => SearchIntent::CommercialInvestigation,
            'local'         => SearchIntent::Local,
            'product'       => SearchIntent::Product,
            'category'      => SearchIntent::Category,
            default         => SearchIntent::Navigational,
        };
    }

    public static function detectFunnel(string $intent): string
    {
        return match ($intent) {
            SearchIntent::Informational => FunnelStage::TOFU,
            SearchIntent::CommercialInvestigation,
            SearchIntent::Navigational,
            SearchIntent::Category => FunnelStage::MOFU,
            default => FunnelStage::BOFU,
        };
    }

    /**
     * @param array<string, int> $signals
     */
    private static function matchSignals(string $text, array $signals): int
    {
        $score = 0;
        foreach ($signals as $signal => $weight) {
            if (str_contains($text, $signal)) {
                $score += $weight;
            }
        }
        return $score;
    }
}
