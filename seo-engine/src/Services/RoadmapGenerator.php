<?php
declare(strict_types=1);

namespace SeoEngine\Services;

use SeoEngine\Models\Difficulty;
use SeoEngine\Models\KeywordData;

/**
 * Generates a phased content roadmap based on difficulty and priority.
 *
 * Phases:
 *   - Quick Wins (3 months): Easy difficulty + priority >= 45
 *   - Medium Term (6 months): Medium difficulty, or easy with lower priority
 *   - Long Term (12 months): Hard difficulty keywords
 *
 * Each phase is sorted by priority score descending.
 *
 * @return array{quickWins: KeywordData[], mediumTerm: KeywordData[], longTerm: KeywordData[]}
 */
final class RoadmapGenerator
{
    /**
     * @param KeywordData[] $keywords
     * @return array{quickWins: KeywordData[], mediumTerm: KeywordData[], longTerm: KeywordData[]}
     */
    public static function generate(array $keywords): array
    {
        $quickWins  = [];
        $mediumTerm = [];
        $longTerm   = [];

        foreach ($keywords as $kw) {
            if ($kw->difficulty === Difficulty::Easy && $kw->priorityScore >= 45) {
                $kw->roadmapPhase = 'برد سریع (۳ ماهه)';
                $quickWins[] = $kw;
            } elseif ($kw->difficulty === Difficulty::Hard) {
                $kw->roadmapPhase = 'بلندمدت (۱۲ ماهه)';
                $longTerm[] = $kw;
            } else {
                $kw->roadmapPhase = 'میان‌مدت (۶ ماهه)';
                $mediumTerm[] = $kw;
            }
        }

        $sorter = fn(KeywordData $a, KeywordData $b) => $b->priorityScore <=> $a->priorityScore;

        usort($quickWins, $sorter);
        usort($mediumTerm, $sorter);
        usort($longTerm, $sorter);

        return [
            'quickWins'  => $quickWins,
            'mediumTerm' => $mediumTerm,
            'longTerm'   => $longTerm,
        ];
    }
}
