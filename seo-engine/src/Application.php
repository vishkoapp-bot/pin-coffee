<?php
declare(strict_types=1);

namespace SeoEngine;

use SeoEngine\Analyzers\SeoAnalyzer;
use SeoEngine\Export\CsvExporter;
use SeoEngine\Export\ExcelExporter;
use SeoEngine\Export\JsonExporter;
use SeoEngine\Models\ClusterData;
use SeoEngine\Models\DashboardStats;
use SeoEngine\Models\KeywordData;
use SeoEngine\Services\ClusterBuilder;
use SeoEngine\Services\ContentStrategyGenerator;
use SeoEngine\Services\KeywordParser;
use SeoEngine\Services\RoadmapGenerator;

/**
 * Main application controller.
 *
 * Handles the request lifecycle:
 * 1. Parse raw keyword input
 * 2. Run SEO analysis
 * 3. Build clusters
 * 4. Generate content strategy
 * 5. Generate roadmap
 * 6. Calculate dashboard stats
 * 7. Render view or handle export
 */
final class Application
{
    /** @var KeywordData[] */
    private array $keywords = [];

    /** @var ClusterData[] */
    private array $clusters = [];

    /** @var array{quickWins: KeywordData[], mediumTerm: KeywordData[], longTerm: KeywordData[]} */
    private array $roadmap = ['quickWins' => [], 'mediumTerm' => [], 'longTerm' => []];

    private ?DashboardStats $stats = null;
    private bool $resultsReady = false;
    private string $rawInput = '';
    private string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
    }

    public function run(): void
    {
        session_start();

        $this->rawInput = $_POST['keywords'] ?? '';

        if ($this->handleExport()) {
            return;
        }

        if (!empty($this->rawInput)) {
            $this->processKeywords();
        }

        $this->render();
    }

    private function handleExport(): bool
    {
        if (!isset($_POST['export'], $_SESSION['seo_kw'])) {
            return false;
        }

        $this->keywords = unserialize($_SESSION['seo_kw']);
        $this->clusters = unserialize($_SESSION['seo_cl'] ?? serialize([]));

        match ($_POST['export']) {
            'csv'   => CsvExporter::export($this->keywords),
            'excel' => ExcelExporter::export($this->keywords),
            'json'  => JsonExporter::export($this->keywords, $this->clusters),
            default => null,
        };

        exit;
    }

    private function processKeywords(): void
    {
        $this->keywords = KeywordParser::parse($this->rawInput);

        if (empty($this->keywords)) {
            return;
        }

        $this->keywords = SeoAnalyzer::analyze($this->keywords);
        $this->clusters = ClusterBuilder::build($this->keywords);
        ContentStrategyGenerator::generate($this->keywords);
        $this->roadmap = RoadmapGenerator::generate($this->keywords);
        $this->stats   = DashboardStats::calculate($this->keywords, $this->clusters, $this->roadmap);

        $this->resultsReady = true;

        $_SESSION['seo_kw'] = serialize($this->keywords);
        $_SESSION['seo_cl'] = serialize($this->clusters);
    }

    private function render(): void
    {
        $app = $this;
        include $this->basePath . '/templates/layout.php';
    }

    /* ─── Template Helpers ─────────────────────────────── */

    public function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function formatNumber(float $number): string
    {
        if ($number >= 1e9) return round($number / 1e9, 1) . 'B';
        if ($number >= 1e6) return round($number / 1e6, 1) . 'M';
        if ($number >= 1e3) return round($number / 1e3, 1) . 'K';
        return (string) round($number);
    }

    public function hasResults(): bool
    {
        return $this->resultsReady;
    }

    public function getStats(): ?DashboardStats
    {
        return $this->stats;
    }

    /** @return KeywordData[] */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /** @return ClusterData[] */
    public function getClusters(): array
    {
        return $this->clusters;
    }

    public function getRoadmap(): array
    {
        return $this->roadmap;
    }

    public function getRawInput(): string
    {
        return $this->rawInput;
    }
}
