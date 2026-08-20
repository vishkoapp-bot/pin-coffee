<?php
declare(strict_types=1);

namespace SeoEngine\Services;

use SeoEngine\Models\ClusterData;
use SeoEngine\Models\KeywordData;
use SeoEngine\Models\SearchIntent;

/**
 * Groups keywords into topical clusters using token overlap analysis.
 *
 * Uses a greedy clustering approach:
 * 1. Sort keywords by priority (desc) so best keywords become pillar topics
 * 2. For each unclustered keyword, check token overlap with existing clusters
 * 3. Assign to best matching cluster or create new one
 * 4. Remaining unclustered keywords are grouped by intent
 */
final class ClusterBuilder
{
    private const MIN_TOKEN_LENGTH = 3;
    private const MAX_CLUSTER_DISPLAY = 10;

    /**
     * @param KeywordData[] $keywords
     * @return ClusterData[]
     */
    public static function build(array $keywords): array
    {
        if (empty($keywords)) {
            return [];
        }

        $clusters    = [];
        $clustered   = [];

        $sorted = $keywords;
        usort($sorted, fn(KeywordData $a, KeywordData $b) =>
            $b->searchVolume <=> $a->searchVolume
        );

        foreach ($sorted as $kw) {
            if (isset($clustered[$kw->keyword])) continue;

            $group = [$kw];
            $clustered[$kw->keyword] = true;

            foreach ($keywords as $candidate) {
                if (isset($clustered[$candidate->keyword])) continue;
                if (self::hasSharedRoot($kw->keyword, $candidate->keyword)) {
                    $group[] = $candidate;
                    $clustered[$candidate->keyword] = true;
                }
            }

            if (count($group) >= 2) {
                $clusters[] = self::buildCluster($kw->keyword, $group);
            } else {
                unset($clustered[$kw->keyword]);
            }
        }

        $unclustered = array_filter(
            $keywords,
            fn(KeywordData $kw) => !isset($clustered[$kw->keyword])
        );

        if (!empty($unclustered)) {
            $groups = [];
            foreach ($unclustered as $kw) {
                $intentKey = $kw->intent ?: 'سایر';
                $groups[$intentKey][] = $kw;
            }
            foreach ($groups as $intentName => $group) {
                $cluster = self::buildCluster($group[0]->keyword, $group);
                $cluster->name = 'گروه ' . $intentName;
                $clusters[] = $cluster;
            }
        }

        usort($clusters, fn(ClusterData $a, ClusterData $b) =>
            $b->clusterStrength <=> $a->clusterStrength
        );

        foreach ($clusters as $cluster) {
            foreach ($cluster->keywords as $kw) {
                $kw->cluster = $cluster->name;
            }
        }

        return $clusters;
    }

    private static function buildCluster(string $pillarName, array $keywords): ClusterData
    {
        $cluster = new ClusterData($pillarName, $pillarName, $keywords);

        foreach ($keywords as $kw) {
            if (in_array($kw->intent, [SearchIntent::Informational, SearchIntent::CommercialInvestigation], true)) {
                $cluster->supportingArticles[] = $kw->keyword;
            }
            if (SearchIntent::isRevenue($kw->intent)) {
                $cluster->moneyPages[] = $kw->keyword;
            }
        }

        $totalVolume  = $cluster->getTotalVolume();
        $avgPriority  = $cluster->getAveragePriority();
        $sizeBonus    = min(20, count($keywords) * 2);
        $volumeBonus  = min(15, $totalVolume / 1000);
        $diversityBonus = self::intentDiversityBonus($keywords);

        $cluster->clusterStrength = round(
            min(100, $avgPriority + $sizeBonus + $volumeBonus + $diversityBonus),
            1
        );

        $sorted = $keywords;
        usort($sorted, fn(KeywordData $a, KeywordData $b) =>
            $b->priorityScore <=> $a->priorityScore
        );

        foreach (array_slice($sorted, 0, 5) as $kw) {
            $cluster->internalLinks[] = [
                'from'   => $pillarName,
                'to'     => $kw->keyword,
                'anchor' => $kw->keyword,
            ];
        }

        return $cluster;
    }

    /**
     * Bonus for clusters that cover multiple intents (better topical coverage).
     * @param KeywordData[] $keywords
     */
    private static function intentDiversityBonus(array $keywords): float
    {
        $intents = array_unique(array_map(fn(KeywordData $kw) => $kw->intent, $keywords));
        return min(10.0, count($intents) * 2.5);
    }

    private static function hasSharedRoot(string $textA, string $textB): bool
    {
        $tokensA = TextNormalizer::tokenize($textA);
        $tokensB = TextNormalizer::tokenize($textB);

        foreach ($tokensA as $ta) {
            if (mb_strlen($ta) < self::MIN_TOKEN_LENGTH) continue;
            foreach ($tokensB as $tb) {
                if (mb_strlen($tb) < self::MIN_TOKEN_LENGTH) continue;
                if (str_contains($ta, $tb) || str_contains($tb, $ta)) {
                    return true;
                }
            }
        }

        return false;
    }
}
