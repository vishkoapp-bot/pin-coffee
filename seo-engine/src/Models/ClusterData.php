<?php
declare(strict_types=1);

namespace SeoEngine\Models;

final class ClusterData
{
    public string $name;
    public string $pillarTopic;
    /** @var KeywordData[] */
    public array $keywords;
    /** @var string[] */
    public array $supportingArticles;
    /** @var string[] */
    public array $moneyPages;
    public float $clusterStrength;
    /** @var array<array{from: string, to: string, anchor: string}> */
    public array $internalLinks;

    public function __construct(
        string $name,
        string $pillarTopic,
        array  $keywords = [],
        array  $supportingArticles = [],
        array  $moneyPages = [],
        float  $clusterStrength = 0.0,
        array  $internalLinks = []
    ) {
        $this->name               = $name;
        $this->pillarTopic        = $pillarTopic;
        $this->keywords           = $keywords;
        $this->supportingArticles = $supportingArticles;
        $this->moneyPages         = $moneyPages;
        $this->clusterStrength    = $clusterStrength;
        $this->internalLinks      = $internalLinks;
    }

    public function getTotalVolume(): int
    {
        return array_sum(array_map(fn(KeywordData $kw) => $kw->searchVolume, $this->keywords));
    }

    public function getAveragePriority(): float
    {
        $count = count($this->keywords);
        if ($count === 0) return 0.0;
        $sum = array_sum(array_map(fn(KeywordData $kw) => $kw->priorityScore, $this->keywords));
        return round($sum / $count, 1);
    }
}
