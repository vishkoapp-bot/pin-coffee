<?php
declare(strict_types=1);

namespace SeoEngine\Export;

use SeoEngine\Models\KeywordData;

final class CsvExporter
{
    private const HEADERS = [
        'کلمه کلیدی', 'حجم جستجو', 'سختی', 'اینتنت', 'قیف',
        'ارزش تجاری', 'فرصت رتبه', 'ترافیک', 'درآمد', 'SERP',
        'اقتدار', 'پیچیدگی', 'اولویت', 'خوشه', 'نوع صفحه',
        'URL', 'H1', 'عنوان متا', 'نوع محتوا', 'تعداد کلمات',
        'اسکیما', 'لینک داخلی', 'فاز',
    ];

    /**
     * @param KeywordData[] $keywords
     */
    public static function export(array $keywords): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="seo-keywords-' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        if (!$out) return;

        fputcsv($out, self::HEADERS);

        foreach ($keywords as $kw) {
            fputcsv($out, [
                $kw->keyword, $kw->searchVolume, $kw->difficulty,
                $kw->intent, $kw->funnel, $kw->businessValue,
                $kw->rankingOpportunity, $kw->trafficPotential,
                $kw->revenuePotential, $kw->serpOpportunity,
                $kw->topicalAuthority, $kw->contentComplexity,
                $kw->priorityScore, $kw->cluster, $kw->pageType,
                $kw->urlSlug, $kw->suggestedH1, $kw->metaTitle,
                $kw->contentType, $kw->wordCount, $kw->schemaMarkup,
                $kw->internalLinkTargets, $kw->roadmapPhase,
            ]);
        }

        fclose($out);
    }
}
