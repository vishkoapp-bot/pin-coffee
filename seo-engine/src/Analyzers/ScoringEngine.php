<?php
declare(strict_types=1);

namespace SeoEngine\Analyzers;

use SeoEngine\Config;
use SeoEngine\Models\Difficulty;
use SeoEngine\Models\KeywordData;
use SeoEngine\Models\SearchIntent;

/**
 * Computes all SEO metric scores for a keyword.
 *
 * Scoring formulas use logarithmic volume scaling, intent-based multipliers,
 * and difficulty decay curves to produce scores that align with real-world
 * SEO ranking patterns and algorithmic behavior.
 *
 * Every metric is normalized to a 0-10 scale.
 */
final class ScoringEngine
{
    /**
     * Business value: how commercially valuable is this keyword?
     *
     * Formula: intentBase + volumeBonus
     *   - intentBase ranges from 2.0 (informational) to 9.0 (transactional)
     *   - volumeBonus uses log10 scaling: min(2.0, log10(volume+1) / 2.5)
     *   - Capped at 10.0
     */
    public static function businessValue(KeywordData $kw): float
    {
        // base by intent aligned to commercial value
        $intentBase = match ($kw->intent) {
            SearchIntent::Transactional          => 9.0,
            SearchIntent::Product                => 8.0,
            SearchIntent::CommercialInvestigation => 7.0,
            SearchIntent::Local                  => 6.5,
            SearchIntent::Category               => 5.5,
            SearchIntent::Navigational           => 4.0,
            default                              => 2.5,
        };

        // funnel stage: bottom-of-funnel (purchase) should increase business value
        $funnelBoost = match ($kw->funnel) {
            'bottom' => 1.5,
            'middle' => 0.8,
            'top'    => 0.4,
            default  => 0.6,
        };

        // volume: use log scaling but bounded; very high volume has diminishing added value
        $volumeBonus = min(2.0, log10(max(1, $kw->searchVolume) + 1) / 2.2);

        // factor in CTR as a proxy for click-share (more clicks => more business value)
        $ctrFactor = min(1.8, 1.0 + ($kw->getCtr() - 0.05) * 3.5);

        $raw = $intentBase * $funnelBoost * $ctrFactor + $volumeBonus;

        return min(10.0, round($raw, 1));
    }

    /**R
     * Ranking opportunity: how likely are we to rank for this keyword?
     *
     * Formula: difficultyBase + longTailBonus
     *   - difficultyBase: Easy=8.5, Medium=5.5, Hard=2.0
     *   - longTailBonus: longer keywords are easier to rank for
     *     min(2.0, (wordCount - 1) * 0.5)
     */
    public static function rankingOpportunity(KeywordData $kw): float
    {
        // use numeric difficulty for smoother decay
        $diffNum = $kw->getDifficultyNumeric(); // 0-100-ish

        // map numeric difficulty to opportunity base (higher numeric difficulty -> lower opportunity)
        $diffBase = max(1.0, 10.0 - ($diffNum / 12.0));

        // long-tail bonus: also consider keyword uniqueness (word count) and presence of modifiers
        $wordCount = $kw->getWordCount();
        $longTailBonus = min(2.5, max(0.0, ($wordCount - 1) * 0.6));

        // penalize when difficulty is extreme (very high numeric difficulty)
        $difficultyPenalty = $diffNum > 75 ? ($diffNum - 75) / 25.0 : 0.0;

        $raw = $diffBase + $longTailBonus - $difficultyPenalty;

        return min(10.0, round(max(0.5, $raw), 1));
    }

    /**
     * Topical authority potential: how much does this keyword
     * contribute to building topical authority?
     *
     * Shorter, broader terms are pillars (higher authority).
     * Commercial intents build stronger authority clusters.
     */
    public static function topicalAuthority(KeywordData $kw): float
    {
        $wordCount = $kw->getWordCount();
        // pillar topics are generally short, high-volume or commercial
        $base = match (true) {
            $wordCount <= 2 => 7.8,
            $wordCount <= 4 => 6.2,
            default         => 4.2,
        };

        // commercial intents and bottom-funnel add more to authority when used strategically
        $intentBonus = SearchIntent::isCommercial($kw->intent) ? 2.0 : 0.7;
        $funnelBonus = $kw->funnel === 'bottom' ? 0.8 : ($kw->funnel === 'middle' ? 0.4 : 0.0);

        // volume supports authority but with strong diminishing returns
        $volumeBonus = min(1.2, log10(max(1, $kw->searchVolume) + 1) / 3.2);

        $raw = $base + $intentBonus + $funnelBonus + $volumeBonus;
        return min(10.0, round($raw, 1));
    }

    /**
     * Long-tail score: how long-tail is this keyword?
     *
     * Uses diminishing returns: score = min(10, wordCount * 1.6 + log2(wordCount))
     */
    public static function longTailScore(KeywordData $kw): float
    {
        $wc = $kw->getWordCount();
        // reward longer queries but penalize when too long (user intent unclear)
        $base = $wc * 1.4 + log($wc + 1, 2);
        $penalty = $wc > 8 ? ($wc - 8) * 0.3 : 0.0;
        $score = $base - $penalty;
        return min(10.0, round(max(0.5, $score), 1));
    }

    /**
     * Traffic potential: estimated monthly traffic if ranked in top 3.
     *
     * Formula: normalize(volume * CTR) on log scale
     * CTR varies by difficulty (proxy for competition).
     */
    public static function trafficPotential(KeywordData $kw): float
    {
        $ctr = $kw->getCtr();
        $estimatedTraffic = $kw->searchVolume * $ctr;

        // factor in ranking opportunity: if we can't rank, traffic potential lowers
        $rankOpp = $kw->rankingOpportunity ?: self::rankingOpportunity($kw);

        $score = log10(max(1, $estimatedTraffic) + 1) * 2.2;
        $score *= (0.6 + ($rankOpp / 10.0) * 0.8); // scale 0.6-1.4 based on rank opp (0-10)

        return round(max(0.5, min(10.0, $score)), 1);
    }

    /**
     * Revenue potential: estimated revenue contribution.
     *
     * Formula: normalize(volume * CTR * conversionRate * AOV)
     * Uses intent-specific conversion rates and difficulty-based CTR.
     */
    public static function revenuePotential(KeywordData $kw): float
    {
        $ctr  = $kw->getCtr();
        $conv = $kw->getConversionRate();

        // use conversion uplift for highly commercial intents and bottom funnel
        $funnelMultiplier = $kw->funnel === 'bottom' ? 1.6 : ($kw->funnel === 'middle' ? 1.1 : 0.85);

        $revenue = $kw->searchVolume * $ctr * $conv * Config::AVG_ORDER_VALUE * $funnelMultiplier;

        // scale into 0-10 range with log and conservative divisor
        $score = log10(max(1, $revenue) + 1) / 2.0;

        return round(max(0.5, min(10.0, $score)), 1);
    }

    /**
     * SERP opportunity: are there feature snippet or SERP feature opportunities?
     *
     * Lower difficulty = more SERP opportunity.
     * Informational queries have more featured snippet potential.
     * Commercial investigation has "People Also Ask" potential.
     */
    public static function serpOpportunity(KeywordData $kw): float
    {
        // SERP features depend on intent and difficulty numeric
        $diffNum = $kw->getDifficultyNumeric();
        $diffFactor = max(0.5, 1.2 - ($diffNum / 120.0));

        $intentBonus = match ($kw->intent) {
            SearchIntent::Informational          => 2.4,
            SearchIntent::CommercialInvestigation => 1.6,
            SearchIntent::Category               => 1.2,
            SearchIntent::Local                  => 1.0,
            default                              => 0.6,
        };

        // presence of 'how', 'best', 'compare' in keyword suggests featured snippets or comparison features
        $lower = mb_strtolower($kw->keyword, 'UTF-8');
        $featureHint = (str_contains($lower, 'چگونه') || str_contains($lower, 'بهترین') || str_contains($lower, 'مقایسه')) ? 1.0 : 0.0;

        $raw = ($intentBonus + $featureHint) * $diffFactor * 3.0;
        return min(10.0, round(max(0.5, $raw), 1));
    }

    /**
     * Content complexity: how complex will the content need to be?
     *
     * Higher = more complex = more effort required.
     * Hard keywords + informational intent = highest complexity.
     */
    public static function contentComplexity(KeywordData $kw): float
    {
        // complexity grows with difficulty numeric and intent that requires expertise
        $diffNum = $kw->getDifficultyNumeric();
        $diffBase = min(8.5, 2.5 + ($diffNum / 15.0));

        $intentAdd = match ($kw->intent) {
            SearchIntent::Informational          => 2.8,
            SearchIntent::CommercialInvestigation => 2.0,
            SearchIntent::Category               => 1.5,
            default                              => 1.0,
        };

        // very long content (recommended wordCount > 1200) increases complexity too
        $recommendedWords = $kw->wordCount ?: 0;
        $lengthPenalty = $recommendedWords > 1200 ? min(1.5, ($recommendedWords - 1200) / 800.0) : 0.0;

        $raw = $diffBase + $intentAdd + $lengthPenalty;
        return min(10.0, round($raw, 1));
    }

    /**
     * Priority Score (0-100): the master composite score.
     *
     * Uses a weighted formula that balances quick-win potential,
     * business impact, and effort required:
     *
     *   priority = Σ(weight_i × metric_i) / Σ(weight_i) × 10
     *
     * Weights reflect real SEO decision-making priorities:
     *   - Ranking opportunity (25%): can we actually rank?
     *   - Business value (20%): is it worth ranking for?
     *   - Traffic potential (15%): how much traffic will we get?
     *   - Revenue potential (15%): how much money will it make?
     *   - SERP opportunity (10%): extra SERP features available?
     *   - Topical authority (10%): does it build our authority?
     *   - Content complexity (5%): inverted — lower complexity is better
     */
    public static function priorityScore(KeywordData $kw): float
    {
        $weights = [
            'ranking'    => 0.25,
            'business'   => 0.20,
            'traffic'    => 0.15,
            'revenue'    => 0.15,
            'serp'       => 0.10,
            'authority'  => 0.10,
            'complexity' => 0.05,
        ];

        // ensure metrics are calculated (use methods if not already set)
        $ranking   = $kw->rankingOpportunity ?: self::rankingOpportunity($kw);
        $business  = $kw->businessValue ?: self::businessValue($kw);
        $traffic   = $kw->trafficPotential ?: self::trafficPotential($kw);
        $revenue   = $kw->revenuePotential ?: self::revenuePotential($kw);
        $serp      = $kw->serpOpportunity ?: self::serpOpportunity($kw);
        $authority = $kw->topicalAuthority ?: self::topicalAuthority($kw);
        $complexity = $kw->contentComplexity ?: self::contentComplexity($kw);

        $invertedComplexity = 10.0 - $complexity;

        $weighted =
            $weights['ranking']    * $ranking +
            $weights['business']   * $business +
            $weights['traffic']    * $traffic +
            $weights['revenue']    * $revenue +
            $weights['serp']       * $serp +
            $weights['authority']  * $authority +
            $weights['complexity'] * $invertedComplexity;

        // add a small multiplier that favors lower difficulty (practical quick wins)
        $diffNum = $kw->getDifficultyNumeric();
        $difficultyMultiplier = 1.0 - min(0.18, $diffNum / 600.0);

        $score = $weighted * 10.0 * $difficultyMultiplier;

        return round(max(0.0, min(100.0, $score)), 1);
    }
}
