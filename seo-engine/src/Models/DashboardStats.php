<?php
declare(strict_types=1);

namespace SeoEngine\Models;

use SeoEngine\Config;

final class DashboardStats
{
    public int   $totalKeywords   = 0;
    public float $avgDifficulty   = 0.0;
    public int   $totalVolume     = 0;
    public int   $quickWinsCount  = 0;
    public int   $revenueKeywords = 0;
    public float $estimatedTraffic  = 0.0;
    public float $estimatedRevenue  = 0.0;
    public int   $totalClusters     = 0;
    public float $avgPriority       = 0.0;

    /** @var array<string, int> */
    public array $priorityDist    = [];
    /** @var array<string, int> */
    public array $intentDist      = [];
    /** @var array<string, int> */
    public array $difficultyDist  = [];
    /** @var array<string, int> */
    public array $funnelDist      = [];
    /** @var array<string, int> */
    public array $clusterSizeDist = [];

    /**
     * @param KeywordData[]  $keywords
     * @param ClusterData[]  $clusters
     * @param array          $roadmap
     */
    public static function calculate(array $keywords, array $clusters, array $roadmap): self
    {
        $stats = new self();
        $stats->totalKeywords = count($keywords);
        $stats->totalClusters = count($clusters);
        $stats->quickWinsCount = count($roadmap['quickWins'] ?? []);

        $diffSum      = 0;
        $prioritySum  = 0.0;
        $intentCounts = [];
        $difficultyCounts = [
            Difficulty::Easy   => 0,
            Difficulty::Medium => 0,
            Difficulty::Hard   => 0,
        ];
        $funnelCounts = [
            FunnelStage::TOFU => 0,
            FunnelStage::MOFU => 0,
            FunnelStage::BOFU => 0,
        ];
        $priBuckets = [
            'بالا (70-100)' => 0,
            'متوسط (40-69)' => 0,
            'پایین (0-39)'  => 0,
        ];

        foreach ($keywords as $kw) {
            $stats->totalVolume += $kw->searchVolume;
            $diffSum            += $kw->getDifficultyNumeric();
            $prioritySum        += $kw->priorityScore;

            $intentCounts[$kw->intent] = ($intentCounts[$kw->intent] ?? 0) + 1;

            if (isset($difficultyCounts[$kw->difficulty])) {
                $difficultyCounts[$kw->difficulty]++;
            }
            if (isset($funnelCounts[$kw->funnel])) {
                $funnelCounts[$kw->funnel]++;
            }

            if ($kw->priorityScore >= 70)      $priBuckets['بالا (70-100)']++;
            elseif ($kw->priorityScore >= 40)  $priBuckets['متوسط (40-69)']++;
            else                               $priBuckets['پایین (0-39)']++;

            if (SearchIntent::isRevenue($kw->intent)) {
                $stats->revenueKeywords++;
            }

            $ctr  = $kw->getCtr();
            $conv = $kw->getConversionRate();
            $stats->estimatedTraffic += $kw->searchVolume * $ctr;
            $stats->estimatedRevenue += $kw->searchVolume * $ctr * $conv * Config::AVG_ORDER_VALUE;
        }

        $n = $stats->totalKeywords;
        $stats->avgDifficulty = $n > 0 ? round($diffSum / $n, 1) : 0.0;
        $stats->avgPriority   = $n > 0 ? round($prioritySum / $n, 1) : 0.0;
        $stats->priorityDist  = $priBuckets;
        $stats->intentDist    = $intentCounts;
        $stats->difficultyDist = $difficultyCounts;
        $stats->funnelDist    = $funnelCounts;

        foreach ($clusters as $cluster) {
            $stats->clusterSizeDist[$cluster->name] = count($cluster->keywords);
        }

        return $stats;
    }
}
