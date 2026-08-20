<?php
declare(strict_types=1);

namespace SeoEngine\Export;

use SeoEngine\Config;
use SeoEngine\Models\ClusterData;
use SeoEngine\Models\KeywordData;

final class JsonExporter
{
    /**
     * @param KeywordData[] $keywords
     * @param ClusterData[] $clusters
     */
    public static function export(array $keywords, array $clusters): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="seo-keywords-' . date('Y-m-d') . '.json"');

        $data = [
            'meta' => [
                'app'      => Config::APP_NAME,
                'version'  => Config::APP_VERSION,
                'exported' => date('Y-m-d H:i:s'),
                'total'    => count($keywords),
            ],
            'keywords' => array_map(fn(KeywordData $kw) => [
                'keyword'    => $kw->keyword,
                'volume'     => $kw->searchVolume,
                'difficulty' => $kw->difficulty,
                'intent'     => $kw->intent,
                'funnel'     => $kw->funnel,
                'scores'     => [
                    'business'   => $kw->businessValue,
                    'ranking'    => $kw->rankingOpportunity,
                    'traffic'    => $kw->trafficPotential,
                    'revenue'    => $kw->revenuePotential,
                    'serp'       => $kw->serpOpportunity,
                    'authority'  => $kw->topicalAuthority,
                    'complexity' => $kw->contentComplexity,
                    'priority'   => $kw->priorityScore,
                ],
                'strategy' => [
                    'page_type'    => $kw->pageType,
                    'url'          => $kw->urlSlug,
                    'h1'           => $kw->suggestedH1,
                    'meta'         => $kw->metaTitle,
                    'content_type' => $kw->contentType,
                    'words'        => $kw->wordCount,
                    'schema'       => $kw->schemaMarkup,
                    'links'        => $kw->internalLinkTargets,
                ],
                'cluster' => $kw->cluster,
                'roadmap' => $kw->roadmapPhase,
            ], $keywords),
            'clusters' => array_map(fn(ClusterData $c) => [
                'name'        => $c->name,
                'pillar'      => $c->pillarTopic,
                'count'       => count($c->keywords),
                'strength'    => $c->clusterStrength,
                'articles'    => $c->supportingArticles,
                'money_pages' => $c->moneyPages,
                'links'       => $c->internalLinks,
            ], $clusters),
        ];

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
