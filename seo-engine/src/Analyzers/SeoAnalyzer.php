<?php
declare(strict_types=1);

namespace SeoEngine\Analyzers;

use SeoEngine\Models\KeywordData;

/**
 * Orchestrates full SEO analysis on a set of keywords.
 * Delegates intent detection and scoring to specialized classes.
 */
final class SeoAnalyzer
{
    /**
     * Run full analysis on all keywords.
     *
     * @param KeywordData[] $keywords
     * @return KeywordData[]
     */
    public static function analyze(array $keywords): array
    {
        foreach ($keywords as $kw) {
            $kw->intent = IntentDetector::detect($kw->keyword);
            $kw->funnel = IntentDetector::detectFunnel($kw->intent);

            $kw->businessValue      = ScoringEngine::businessValue($kw);
            $kw->rankingOpportunity = ScoringEngine::rankingOpportunity($kw);
            $kw->topicalAuthority   = ScoringEngine::topicalAuthority($kw);
            $kw->longTailScore      = ScoringEngine::longTailScore($kw);
            $kw->trafficPotential   = ScoringEngine::trafficPotential($kw);
            $kw->revenuePotential   = ScoringEngine::revenuePotential($kw);
            $kw->serpOpportunity    = ScoringEngine::serpOpportunity($kw);
            $kw->contentComplexity  = ScoringEngine::contentComplexity($kw);

            $kw->priorityScore = ScoringEngine::priorityScore($kw);
        }

        usort($keywords, fn(KeywordData $a, KeywordData $b) =>
            $b->priorityScore <=> $a->priorityScore
        );

        return $keywords;
    }
}
