<?php
declare(strict_types=1);

namespace SeoEngine\Services;

use SeoEngine\Models\Difficulty;
use SeoEngine\Models\KeywordData;
use SeoEngine\Models\SearchIntent;
use SeoEngine\Analyzers\ScoringEngine;
use SeoEngine\Services\TextNormalizer;

/**
 * Generates actionable content strategy recommendations for each keyword.
 *
 * Produces page type, URL slug, H1, meta title, content type,
 * word count target, schema markup, and internal link suggestions.
 */
final class ContentStrategyGenerator
{
    /**
     * @param KeywordData[] $keywords
     */
    public static function generate(array $keywords): void
    {
        foreach ($keywords as $kw) {
            $kw->pageType           = self::pageType($kw);
            $kw->urlSlug            = TextNormalizer::slugify($kw->keyword);
            $kw->suggestedH1        = self::generateH1($kw);
            $kw->metaTitle          = mb_substr(self::generateMetaTitle($kw), 0, 60, 'UTF-8');
            $kw->contentType        = self::contentType($kw);
            $kw->wordCount          = self::targetWordCount($kw);
            $kw->schemaMarkup       = self::schemaRecommendation($kw);
            $kw->internalLinkTargets = self::suggestInternalLinks($kw, $keywords);
            // enhanced outputs
            $kw->metaDescription    = self::generateMetaDescription($kw);
            $kw->readingTime        = self::estimateReadingTime($kw);
            $kw->suggestedHeadings  = self::suggestHeadings($kw);
            $kw->serpFeatureTargets = self::suggestSerpFeatures($kw);
            $kw->eeatRecommendations = self::eeatRecommendations($kw);
            $kw->targetQuestions    = self::suggestTargetQuestions($kw);
            $kw->canonical          = '/'. $kw->urlSlug;
        }
    }

    private static function pageType(KeywordData $kw): string
    {
        return match ($kw->intent) {
            SearchIntent::Transactional, SearchIntent::Product  => 'صفحه محصول',
            SearchIntent::Category                              => 'صفحه دسته‌بندی',
            SearchIntent::Informational                         => 'مقاله بلاگ',
            SearchIntent::CommercialInvestigation               => 'صفحه مقایسه',
            SearchIntent::Local                                 => 'صفحه محلی',
            default                                             => 'لندینگ پیج',
        };
    }

    private static function contentType(KeywordData $kw): string
    {
        $lower = TextNormalizer::toLower($kw->keyword);

        return match (true) {
            str_contains($lower, 'مقایسه') || str_contains($lower, 'بهترین')  => 'مقاله مقایسه‌ای',
            str_contains($lower, 'آموزش') || str_contains($lower, 'نحوه')
                || str_contains($lower, 'چگونه')                               => 'آموزش گام به گام',
            str_contains($lower, 'قیمت')                                       => 'لیست قیمت',
            str_contains($lower, 'خرید')                                       => 'صفحه فرود فروش',
            str_contains($lower, 'تفاوت')                                      => 'مقاله مقایسه‌ای',
            str_contains($lower, 'نقد') || str_contains($lower, 'بررسی')       => 'بررسی تخصصی',
            str_contains($lower, 'انواع') || str_contains($lower, 'لیست')      => 'لیست جامع',
            default                                                            => 'مقاله جامع',
        };
    }

    private static function generateH1(KeywordData $kw): string
    {
        return match ($kw->intent) {
            SearchIntent::Informational            => 'راهنمای جامع ' . $kw->keyword,
            SearchIntent::Transactional            => $kw->keyword . ' با بهترین قیمت',
            SearchIntent::CommercialInvestigation  => 'بهترین ' . $kw->keyword . ' — مقایسه و بررسی',
            SearchIntent::Product                  => $kw->keyword . ' — مشخصات، قیمت و خرید',
            SearchIntent::Category                 => $kw->keyword . ' — دسته‌بندی کامل',
            SearchIntent::Local                    => $kw->keyword . ' نزدیک شما',
            default                                => $kw->keyword,
        };
    }

    private static function generateMetaTitle(KeywordData $kw): string
    {
        return match ($kw->intent) {
            SearchIntent::Informational            => $kw->keyword . ' | راهنمای کامل ۱۴۰۴',
            SearchIntent::Transactional            => $kw->keyword . ' | خرید با تخفیف ویژه',
            SearchIntent::CommercialInvestigation  => 'بهترین ' . $kw->keyword . ' | مقایسه ۱۴۰۴',
            SearchIntent::Product                  => $kw->keyword . ' | قیمت، مشخصات و نظرات',
            SearchIntent::Category                 => $kw->keyword . ' | دسته‌بندی و مقایسه',
            SearchIntent::Local                    => $kw->keyword . ' | نزدیک‌ترین مراکز',
            default                                => $kw->keyword . ' | صفحه رسمی',
        };
    }

    /**
     * Target word count based on intent and difficulty.
     * Harder keywords need deeper content. Informational needs most depth.
     */
    private static function targetWordCount(KeywordData $kw): int
    {
        $base = match ($kw->intent) {
            SearchIntent::Informational            => 2500,
            SearchIntent::CommercialInvestigation  => 2000,
            SearchIntent::Category                 => 1500,
            SearchIntent::Product                  => 800,
            SearchIntent::Transactional            => 600,
            SearchIntent::Local                    => 800,
            default                                => 500,
        };

        $multiplier = match ($kw->difficulty) {
            Difficulty::Hard   => 1.4,
            Difficulty::Medium => 1.15,
            default            => 1.0,
        };

        return (int) round($base * $multiplier);
    }

    private static function generateMetaDescription(KeywordData $kw): string
    {
        $base = match ($kw->intent) {
            SearchIntent::Informational => 'راهنمای کامل و به‌روز درباره ' . $kw->keyword . ' — نکات، مثال‌ها و بهترین راهکارها.',
            SearchIntent::Transactional => 'خرید ' . $kw->keyword . ' با بهترین قیمت و شرایط ارسال؛ مشاهده موجودی و تخفیف‌ها.',
            default => $kw->keyword . ' — اطلاعات کاربردی و منابع مفید.'
        };

        // shorten to ~155 characters
        return mb_substr($base, 0, 155, 'UTF-8');
    }

    private static function estimateReadingTime(KeywordData $kw): int
    {
        // reading speed ~200 words/minute, add 20% for images/figures
        $words = $kw->wordCount ?: 800;
        $minutes = (int) max(1, round(($words / 200) * 1.2));
        return $minutes;
    }

    private static function suggestHeadings(KeywordData $kw): string
    {
        $h2 = [];
        // core headings based on intent
        if ($kw->intent === SearchIntent::Informational) {
            $h2[] = 'تعاریف و مفاهیم کلیدی';
            $h2[] = 'راهنما و مراحل انجام';
            $h2[] = 'سوالات متداول';
        } elseif ($kw->intent === SearchIntent::Transactional || $kw->intent === SearchIntent::Product) {
            $h2[] = 'ویژگی‌ها و مشخصات';
            $h2[] = 'قیمت و نحوه خرید';
            $h2[] = 'نقد و بررسی کاربران';
        } else {
            $h2[] = 'مقدمه';
            $h2[] = 'نکات مهم';
            $h2[] = 'نتیجه‌گیری';
        }

        // add intent-specific headings
        if (str_contains(mb_strtolower($kw->keyword, 'UTF-8'), 'چگونه') || $kw->contentType === 'آموزش گام به گام') {
            array_unshift($h2, 'مراحل گام به گام');
        }

        return implode(' | ', array_slice($h2, 0, 6));
    }

    private static function suggestSerpFeatures(KeywordData $kw): string
    {
        $features = [];
        $serp = ScoringEngine::serpOpportunity($kw);

        if ($kw->intent === SearchIntent::Informational && $serp >= 6.0) {
            $features[] = 'Featured Snippet';
            $features[] = 'People Also Ask';
            $features[] = 'FAQ';
        }

        if ($kw->intent === SearchIntent::Product || $kw->intent === SearchIntent::Transactional) {
            $features[] = 'Product Rich Result';
            $features[] = 'Review Snippet';
        }

        if ($kw->intent === SearchIntent::Local) {
            $features[] = 'Local Pack';
            $features[] = 'Maps Pin';
        }

        if (empty($features)) {
            $features[] = 'Title/Meta + Internal Links';
        }

        return implode(' | ', array_unique($features));
    }

    private static function eeatRecommendations(KeywordData $kw): string
    {
        $items = [];

        // E-E-A-T recommendations based on intent and difficulty
        if ($kw->getDifficultyNumeric() >= 60) {
            $items[] = 'گزارشات و منابع تحقیقاتی معتبر را نقل کنید';
            $items[] = 'محتوا را با نقل قول از منابع موثق پشتیبانی کنید';
        }

        if ($kw->intent === SearchIntent::Informational) {
            $items[] = 'نویسنده با تخصص نمایش داده شود (Byline)';
            $items[] = 'تأیید منابع و لینک به مراجع';
        }

        if ($kw->intent === SearchIntent::Product || $kw->intent === SearchIntent::Transactional) {
            $items[] = 'مشخصات کامل محصول و مقایسه با رقبا';
            $items[] = 'نقد و بررسی کاربر و امتیازدهی';
        }

        return implode(' | ', $items ?: ['Basic on-page SEO: headings, meta, schema']);
    }

    private static function suggestTargetQuestions(KeywordData $kw): string
    {
        $questions = [];
        $lower = mb_strtolower($kw->keyword, 'UTF-8');

        if ($kw->intent === SearchIntent::Informational) {
            $questions[] = 'چگونه ' . $kw->keyword . ' را انجام دهیم؟';
            $questions[] = 'مزایا و معایب ' . $kw->keyword . ' چیست؟';
            $questions[] = 'ابزارها و منابع برای ' . $kw->keyword . ' کدامند؟';
        }

        if (str_contains($lower, 'قیمت') || $kw->intent === SearchIntent::Product) {
            $questions[] = $kw->keyword . ' چقدر هزینه دارد؟';
            $questions[] = 'ارزان‌ترین گزینه برای ' . $kw->keyword . ' چیست؟';
        }

        if (empty($questions)) {
            $questions[] = 'آیا ' . $kw->keyword . ' مناسب من است؟';
        }

        return implode(' | ', array_slice(array_unique($questions), 0, 6));
    }

    private static function schemaRecommendation(KeywordData $kw): string
    {
        return match ($kw->intent) {
            SearchIntent::Product,
            SearchIntent::Transactional            => 'Product, Offer, AggregateRating',
            SearchIntent::Informational            => 'Article, FAQPage, BreadcrumbList',
            SearchIntent::CommercialInvestigation  => 'Article, ItemList, Review',
            SearchIntent::Category                 => 'ItemList, BreadcrumbList, CollectionPage',
            SearchIntent::Local                    => 'LocalBusiness, GeoCoordinates, Review',
            default                                => 'WebPage, BreadcrumbList',
        };
    }

    /**
     * Suggest up to 3 internal link targets based on token overlap and priority.
     *
     * @param KeywordData[] $allKeywords
     */
    private static function suggestInternalLinks(KeywordData $kw, array $allKeywords): string
    {
        $links  = [];
        $tokens = TextNormalizer::tokenize($kw->keyword);

        foreach ($allKeywords as $other) {
            if ($other->keyword === $kw->keyword) continue;
            if ($other->priorityScore <= 30) continue;

            $otherTokens = TextNormalizer::tokenize($other->keyword);
            $overlap     = count(array_intersect($tokens, $otherTokens));

            if ($overlap >= 1) {
                // prioritize higher priorityScore targets
                $links[] = $other->keyword . ' (priority:' . round($other->priorityScore,1) . ')';
            }
            if (count($links) >= 3) break;
        }

        return implode(' | ', $links);
    }
}
