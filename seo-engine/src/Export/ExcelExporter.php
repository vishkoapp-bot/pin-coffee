<?php
declare(strict_types=1);

namespace SeoEngine\Export;

use SeoEngine\Models\KeywordData;

final class ExcelExporter
{
    private const HEADERS = [
        'کلمه کلیدی', 'حجم', 'سختی', 'اینتنت', 'قیف',
        'ارزش', 'فرصت', 'ترافیک', 'درآمد', 'SERP',
        'اقتدار', 'پیچیدگی', 'اولویت', 'خوشه', 'فاز',
    ];

    /**
     * @param KeywordData[] $keywords
     */
    public static function export(array $keywords): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="seo-keywords-' . date('Y-m-d') . '.xls"');

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
        $xml .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
        $xml .= '<Worksheet ss:Name="Keywords"><Table>';

        $xml .= '<Row>';
        foreach (self::HEADERS as $header) {
            $xml .= '<Cell><Data ss:Type="String">'
                  . htmlspecialchars($header, ENT_XML1, 'UTF-8')
                  . '</Data></Cell>';
        }
        $xml .= '</Row>';

        foreach ($keywords as $kw) {
            $xml .= '<Row>';
            $xml .= self::stringCell($kw->keyword);
            $xml .= self::numberCell($kw->searchVolume);
            $xml .= self::stringCell($kw->difficulty);
            $xml .= self::stringCell($kw->intent);
            $xml .= self::stringCell($kw->funnel);

            $scores = [
                $kw->businessValue, $kw->rankingOpportunity,
                $kw->trafficPotential, $kw->revenuePotential,
                $kw->serpOpportunity, $kw->topicalAuthority,
                $kw->contentComplexity, $kw->priorityScore,
            ];
            foreach ($scores as $val) {
                $xml .= self::numberCell($val);
            }

            $xml .= self::stringCell($kw->cluster);
            $xml .= self::stringCell($kw->roadmapPhase);
            $xml .= '</Row>';
        }

        $xml .= '</Table></Worksheet></Workbook>';
        echo $xml;
    }

    private static function stringCell(string $value): string
    {
        return '<Cell><Data ss:Type="String">'
             . htmlspecialchars($value, ENT_XML1, 'UTF-8')
             . '</Data></Cell>';
    }

    private static function numberCell(int|float $value): string
    {
        return '<Cell><Data ss:Type="Number">' . $value . '</Data></Cell>';
    }
}
