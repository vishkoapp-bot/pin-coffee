<?php
declare(strict_types=1);
/**
 * SEO Keyword Prioritization Engine v2.0
 * Enterprise-level single-file PHP 8.3 application
 * 
 * Features: Keyword parsing, SEO analysis, clustering, content strategy,
 * roadmap generation, Chart.js visualization, CSV/Excel/JSON export
 * Premium SaaS-style dark-mode RTL dashboard
 */

// ─── Configuration ───────────────────────────────────────────────────────────
const APP_NAME = 'SEO Keyword Engine';
const APP_VERSION = '2.0.0';
const MAX_KEYWORDS = 100000;

// ─── Enums ───────────────────────────────────────────────────────────────────

enum SearchIntent: string {
    case Informational = 'اطلاعاتی';
    case CommercialInvestigation = 'بررسی تجاری';
    case Transactional = 'تراکنشی';
    case Navigational = 'ناوبری';
    case Local = 'محلی';
    case Product = 'محصول';
    case Category = 'دسته‌بندی';
}

enum FunnelStage: string {
    case TOFU = 'TOFU';
    case MOFU = 'MOFU';
    case BOFU = 'BOFU';
}

enum Difficulty: string {
    case Easy = 'آسان';
    case Medium = 'متوسط';
    case Hard = 'سخت';
}

enum PageType: string {
    case Product = 'صفحه محصول';
    case Category = 'صفحه دسته‌بندی';
    case Blog = 'مقاله بلاگ';
    case Landing = 'لندینگ پیج';
    case Comparison = 'صفحه مقایسه';
    case Guide = 'راهنمای جامع';
    case FAQ = 'صفحه سوالات متداول';
    case Local = 'صفحه محلی';
}

enum ContentType: string {
    case Article = 'مقاله';
    case ProductPage = 'صفحه محصول';
    case CategoryPage = 'صفحه دسته‌بندی';
    case LandingPage = 'لندینگ پیج';
    case ComparisonArticle = 'مقاله مقایسه‌ای';
    case HowTo = 'آموزش گام به گام';
    case Listicle = 'لیستیکل';
    case Review = 'بررسی و نقد';
}

// ─── Data Models ─────────────────────────────────────────────────────────────

final class KeywordData {
    public string $keyword;
    public int $searchVolume;
    public Difficulty $difficulty;
    public ?SearchIntent $intent = null;
    public ?FunnelStage $funnel = null;
    public float $businessValue = 0;
    public float $rankingOpportunity = 0;
    public float $topicalAuthority = 0;
    public float $longTailScore = 0;
    public float $trafficPotential = 0;
    public float $revenuePotential = 0;
    public float $serpOpportunity = 0;
    public float $contentComplexity = 0;
    public float $priorityScore = 0;
    public string $cluster = '';
    public string $pageType = '';
    public string $urlSlug = '';
    public string $suggestedH1 = '';
    public string $metaTitle = '';
    public string $contentType = '';
    public int $wordCount = 0;
    public string $schemaMarkup = '';
    public string $internalLinkTargets = '';
    public string $roadmapPhase = '';

    public function __construct(string $keyword, int $searchVolume, Difficulty $difficulty) {
        $this->keyword = $keyword;
        $this->searchVolume = $searchVolume;
        $this->difficulty = $difficulty;
    }

    public function getDifficultyNumeric(): int {
        return match($this->difficulty) {
            Difficulty::Easy => 25,
            Difficulty::Medium => 55,
            Difficulty::Hard => 85,
        };
    }
}

final class ClusterData {
    /** @param KeywordData[] $keywords */
    public function __construct(
        public string $name,
        public string $pillarTopic,
        public array $keywords = [],
        public array $supportingArticles = [],
        public array $moneyPages = [],
        public float $clusterStrength = 0,
        public array $internalLinks = [],
    ) {}
}

// ─── Text Normalizer ─────────────────────────────────────────────────────────

final class TextNormalizer {
    /** Normalize Arabic characters to Persian equivalents and clean whitespace */
    public static function normalize(string $text): string {
        $map = [
            'ك' => 'ک', 'ي' => 'ی', 'ى' => 'ی',
            '٤' => '۴', '٥' => '۵', '٦' => '۶',
            "\xE2\x80\x8C" => "\xE2\x80\x8C", // keep ZWNJ
        ];
        $text = str_replace(array_keys($map), array_values($map), $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    /** Generate a URL-safe slug from Persian text */
    public static function slugify(string $text): string {
        $text = self::normalize($text);
        $text = str_replace(' ', '-', $text);
        $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text) ?? $text;
        $text = preg_replace('/-+/', '-', $text) ?? $text;
        return trim($text, '-');
    }
}

// ─── Keyword Parser ──────────────────────────────────────────────────────────

final class KeywordParser {
    /**
     * Parse raw input supporting pipe, comma, tab, newline separated formats
     * @return KeywordData[]
     */
    public static function parse(string $rawInput): array {
        $keywords = [];
        $seen = [];
        $lines = preg_split('/\r?\n/', trim($rawInput));
        if ($lines === false) return [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // Detect separator: pipe, comma, or tab
            $separator = '|';
            if (str_contains($line, '|')) {
                $separator = '|';
            } elseif (str_contains($line, "\t")) {
                $separator = "\t";
            } elseif (str_contains($line, ',')) {
                $separator = ',';
            }

            $parts = explode($separator, $line);
            if (count($parts) < 3) continue;

            $keyword = TextNormalizer::normalize(trim($parts[0]));
            $volume = trim($parts[1]);
            $diff = TextNormalizer::normalize(trim($parts[2]));

            if ($keyword === '') continue;

            // Parse volume — support Persian digits
            $volume = self::persianToLatin($volume);
            $volumeInt = (int)preg_replace('/[^0-9]/', '', $volume);

            // Parse difficulty
            $difficulty = self::parseDifficulty($diff);
            if ($difficulty === null) continue;

            // Deduplicate
            $key = mb_strtolower($keyword, 'UTF-8');
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $keywords[] = new KeywordData($keyword, $volumeInt, $difficulty);

            if (count($keywords) >= MAX_KEYWORDS) break;
        }

        return $keywords;
    }

    private static function parseDifficulty(string $text): ?Difficulty {
        $text = mb_strtolower(trim($text), 'UTF-8');
        return match(true) {
            in_array($text, ['آسان', 'easy', 'ساده', 'راحت', 'کم'], true) => Difficulty::Easy,
            in_array($text, ['متوسط', 'medium', 'معمولی', 'نرمال'], true) => Difficulty::Medium,
            in_array($text, ['سخت', 'hard', 'difficult', 'دشوار', 'زیاد'], true) => Difficulty::Hard,
            default => null,
        };
    }

    private static function persianToLatin(string $text): string {
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $latin   = ['0','1','2','3','4','5','6','7','8','9'];
        return str_replace($persian, $latin, $text);
    }
}

// ─── SEO Analysis Engine ─────────────────────────────────────────────────────

final class SeoAnalysisEngine {
    // Intent detection signal words
    private const TRANSACTIONAL_SIGNALS = [
        'خرید', 'قیمت', 'سفارش', 'ارزان', 'فروش', 'تخفیف',
        'بهترین قیمت', 'موجود', 'اصل', 'ارسال', 'فوری',
    ];
    private const INFORMATIONAL_SIGNALS = [
        'چیست', 'چگونه', 'آموزش', 'راهنما', 'نحوه', 'چرا',
        'تفاوت', 'معرفی', 'بررسی', 'مقایسه', 'آیا',
    ];
    private const COMMERCIAL_SIGNALS = [
        'بهترین', 'مقایسه', 'بررسی', 'نقد', 'رتبه‌بندی',
        'لیست', 'پیشنهاد', 'انتخاب', 'راهنمای خرید',
    ];
    private const LOCAL_SIGNALS = [
        'نزدیک', 'تهران', 'اصفهان', 'شیراز', 'مشهد', 'تبریز',
        'فروشگاه', 'آدرس', 'نمایندگی', 'شعبه',
    ];
    private const PRODUCT_SIGNALS = [
        'مدل', 'سایز', 'رنگ', 'برند', 'جنس', 'نوع',
    ];
    private const CATEGORY_SIGNALS = [
        'انواع', 'لیست', 'دسته', 'مجموعه', 'کالکشن',
    ];

    /**
     * Analyze all keywords and compute scores
     * @param KeywordData[] $keywords
     * @return KeywordData[]
     */
    public static function analyze(array $keywords): array {
        foreach ($keywords as $kw) {
            $kw->intent = self::detectIntent($kw->keyword);
            $kw->funnel = self::detectFunnel($kw->intent, $kw->keyword);
            $kw->businessValue = self::calcBusinessValue($kw);
            $kw->rankingOpportunity = self::calcRankingOpportunity($kw);
            $kw->topicalAuthority = self::calcTopicalAuthority($kw);
            $kw->longTailScore = self::calcLongTail($kw);
            $kw->trafficPotential = self::calcTrafficPotential($kw);
            $kw->revenuePotential = self::calcRevenuePotential($kw);
            $kw->serpOpportunity = self::calcSerpOpportunity($kw);
            $kw->contentComplexity = self::calcContentComplexity($kw);
            $kw->priorityScore = self::calcPriorityScore($kw);
        }

        // Sort by priority descending
        usort($keywords, fn(KeywordData $a, KeywordData $b) => $b->priorityScore <=> $a->priorityScore);

        return $keywords;
    }

    private static function detectIntent(string $keyword): SearchIntent {
        $kw = mb_strtolower($keyword, 'UTF-8');

        $scores = [
            'transactional' => 0,
            'informational' => 0,
            'commercial' => 0,
            'local' => 0,
            'product' => 0,
            'category' => 0,
            'navigational' => 0,
        ];

        foreach (self::TRANSACTIONAL_SIGNALS as $signal) {
            if (str_contains($kw, $signal)) $scores['transactional'] += 2;
        }
        foreach (self::INFORMATIONAL_SIGNALS as $signal) {
            if (str_contains($kw, $signal)) $scores['informational'] += 2;
        }
        foreach (self::COMMERCIAL_SIGNALS as $signal) {
            if (str_contains($kw, $signal)) $scores['commercial'] += 2;
        }
        foreach (self::LOCAL_SIGNALS as $signal) {
            if (str_contains($kw, $signal)) $scores['local'] += 2;
        }
        foreach (self::PRODUCT_SIGNALS as $signal) {
            if (str_contains($kw, $signal)) $scores['product'] += 2;
        }
        foreach (self::CATEGORY_SIGNALS as $signal) {
            if (str_contains($kw, $signal)) $scores['category'] += 2;
        }

        // Default scoring based on word count
        $wordCount = count(explode(' ', $keyword));
        if ($wordCount <= 2) $scores['navigational'] += 1;
        if ($wordCount >= 4) $scores['informational'] += 1;

        $maxType = array_keys($scores, max($scores))[0];

        return match($maxType) {
            'transactional' => SearchIntent::Transactional,
            'informational' => SearchIntent::Informational,
            'commercial' => SearchIntent::CommercialInvestigation,
            'local' => SearchIntent::Local,
            'product' => SearchIntent::Product,
            'category' => SearchIntent::Category,
            'navigational' => SearchIntent::Navigational,
            default => SearchIntent::Informational,
        };
    }

    private static function detectFunnel(SearchIntent $intent, string $keyword): FunnelStage {
        return match($intent) {
            SearchIntent::Informational => FunnelStage::TOFU,
            SearchIntent::CommercialInvestigation => FunnelStage::MOFU,
            SearchIntent::Transactional => FunnelStage::BOFU,
            SearchIntent::Navigational => FunnelStage::MOFU,
            SearchIntent::Local => FunnelStage::BOFU,
            SearchIntent::Product => FunnelStage::BOFU,
            SearchIntent::Category => FunnelStage::MOFU,
        };
    }

    /** Business value: higher for transactional/product, high volume */
    private static function calcBusinessValue(KeywordData $kw): float {
        $base = match($kw->intent) {
            SearchIntent::Transactional => 8.0,
            SearchIntent::Product => 7.5,
            SearchIntent::CommercialInvestigation => 7.0,
            SearchIntent::Local => 6.5,
            SearchIntent::Category => 6.0,
            SearchIntent::Navigational => 4.0,
            SearchIntent::Informational => 3.0,
        };
        // Volume bonus
        $volumeBonus = min(2.0, ($kw->searchVolume / 5000) * 2);
        return min(10.0, round($base + $volumeBonus, 1));
    }

    /** Ranking opportunity: easier difficulty = higher opportunity */
    private static function calcRankingOpportunity(KeywordData $kw): float {
        $diffScore = match($kw->difficulty) {
            Difficulty::Easy => 9.0,
            Difficulty::Medium => 6.0,
            Difficulty::Hard => 3.0,
        };
        // Long tail bonus
        $words = count(explode(' ', $kw->keyword));
        $ltBonus = min(1.0, ($words - 1) * 0.25);
        return min(10.0, round($diffScore + $ltBonus, 1));
    }

    /** Topical authority contribution based on cluster potential */
    private static function calcTopicalAuthority(KeywordData $kw): float {
        $words = count(explode(' ', $kw->keyword));
        $base = match(true) {
            $words <= 2 => 7.0,  // head term, strong pillar potential
            $words <= 4 => 6.0,
            default => 5.0,
        };
        // Commercial/product terms contribute more
        $intentBonus = match($kw->intent) {
            SearchIntent::Category => 2.0,
            SearchIntent::Product => 1.5,
            SearchIntent::CommercialInvestigation => 1.5,
            default => 0.5,
        };
        return min(10.0, round($base + $intentBonus, 1));
    }

    /** Long tail score: more words = higher */
    private static function calcLongTail(KeywordData $kw): float {
        $words = count(explode(' ', $kw->keyword));
        return min(10.0, round($words * 1.8, 1));
    }

    /** Traffic potential based on volume and difficulty */
    private static function calcTrafficPotential(KeywordData $kw): float {
        $ctr = match($kw->difficulty) {
            Difficulty::Easy => 0.35,
            Difficulty::Medium => 0.15,
            Difficulty::Hard => 0.05,
        };
        $estimatedTraffic = $kw->searchVolume * $ctr;
        $score = min(10.0, ($estimatedTraffic / 500) * 10);
        return round(max(1.0, $score), 1);
    }

    /** Revenue potential based on intent and volume */
    private static function calcRevenuePotential(KeywordData $kw): float {
        $convRate = match($kw->intent) {
            SearchIntent::Transactional => 0.05,
            SearchIntent::Product => 0.04,
            SearchIntent::Local => 0.035,
            SearchIntent::CommercialInvestigation => 0.02,
            SearchIntent::Category => 0.015,
            SearchIntent::Navigational => 0.01,
            SearchIntent::Informational => 0.005,
        };
        $avgOrderValue = 500000; // Toman
        $ctr = match($kw->difficulty) {
            Difficulty::Easy => 0.30,
            Difficulty::Medium => 0.12,
            Difficulty::Hard => 0.04,
        };
        $revenue = $kw->searchVolume * $ctr * $convRate * $avgOrderValue;
        $score = min(10.0, ($revenue / 5000000) * 10);
        return round(max(1.0, $score), 1);
    }

    /** SERP opportunity based on difficulty and intent mix */
    private static function calcSerpOpportunity(KeywordData $kw): float {
        $base = match($kw->difficulty) {
            Difficulty::Easy => 8.5,
            Difficulty::Medium => 5.5,
            Difficulty::Hard => 2.5,
        };
        // Featured snippet opportunity for informational
        $bonus = match($kw->intent) {
            SearchIntent::Informational => 1.5,
            SearchIntent::CommercialInvestigation => 1.0,
            default => 0.5,
        };
        return min(10.0, round($base + $bonus, 1));
    }

    /** Content complexity: how much effort is needed */
    private static function calcContentComplexity(KeywordData $kw): float {
        $base = match($kw->intent) {
            SearchIntent::Informational => 7.0,
            SearchIntent::CommercialInvestigation => 6.5,
            SearchIntent::Category => 5.0,
            SearchIntent::Product => 4.0,
            SearchIntent::Transactional => 3.5,
            SearchIntent::Local => 4.0,
            SearchIntent::Navigational => 3.0,
        };
        $diffBonus = match($kw->difficulty) {
            Difficulty::Hard => 2.0,
            Difficulty::Medium => 1.0,
            Difficulty::Easy => 0.0,
        };
        return min(10.0, round($base + $diffBonus, 1));
    }

    /**
     * Weighted priority score (0-100):
     * 30% Business Value + 20% Ranking Opportunity + 15% Revenue Potential
     * + 15% Traffic Potential + 10% Topical Authority + 10% SERP Opportunity
     */
    private static function calcPriorityScore(KeywordData $kw): float {
        $score = ($kw->businessValue * 0.30 * 10)
               + ($kw->rankingOpportunity * 0.20 * 10)
               + ($kw->revenuePotential * 0.15 * 10)
               + ($kw->trafficPotential * 0.15 * 10)
               + ($kw->topicalAuthority * 0.10 * 10)
               + ($kw->serpOpportunity * 0.10 * 10);
        return round(min(100.0, max(0.0, $score)), 1);
    }
}

// ─── Keyword Clustering Engine ───────────────────────────────────────────────

final class KeywordClusterer {
    /**
     * Create semantic clusters from analyzed keywords
     * Uses token overlap + intent grouping for clustering
     * @param KeywordData[] $keywords
     * @return ClusterData[]
     */
    public static function cluster(array $keywords): array {
        $clusters = [];
        $assigned = [];

        // Extract all unique tokens for TF analysis
        $tokenIndex = [];
        foreach ($keywords as $idx => $kw) {
            $tokens = self::tokenize($kw->keyword);
            foreach ($tokens as $token) {
                $tokenIndex[$token][] = $idx;
            }
        }

        // Find head terms (1-2 word keywords with high volume) as pillar candidates
        $pillarCandidates = [];
        foreach ($keywords as $idx => $kw) {
            $wordCount = count(explode(' ', $kw->keyword));
            if ($wordCount <= 2 && $kw->searchVolume >= 100) {
                $pillarCandidates[$idx] = $kw;
            }
        }

        // Sort pillar candidates by volume desc
        uasort($pillarCandidates, fn($a, $b) => $b->searchVolume <=> $a->searchVolume);

        // Build clusters around pillars
        foreach ($pillarCandidates as $pillarIdx => $pillar) {
            if (isset($assigned[$pillarIdx])) continue;

            $clusterKeywords = [$pillar];
            $assigned[$pillarIdx] = true;
            $pillarTokens = self::tokenize($pillar->keyword);

            // Find related keywords by token overlap
            foreach ($keywords as $idx => $kw) {
                if (isset($assigned[$idx])) continue;
                $kwTokens = self::tokenize($kw->keyword);
                $overlap = count(array_intersect($pillarTokens, $kwTokens));
                $similarity = count($pillarTokens) > 0
                    ? $overlap / count($pillarTokens)
                    : 0;

                if ($similarity >= 0.5 || self::hasSharedRoot($pillar->keyword, $kw->keyword)) {
                    $clusterKeywords[] = $kw;
                    $assigned[$idx] = true;
                }
            }

            if (count($clusterKeywords) >= 1) {
                $cluster = self::buildCluster($pillar->keyword, $clusterKeywords);
                $clusters[] = $cluster;
            }
        }

        // Assign remaining unclustered keywords
        $unclustered = [];
        foreach ($keywords as $idx => $kw) {
            if (!isset($assigned[$idx])) {
                $unclustered[] = $kw;
            }
        }

        if (!empty($unclustered)) {
            // Group remaining by intent
            $intentGroups = [];
            foreach ($unclustered as $kw) {
                $intentKey = $kw->intent?->value ?? 'سایر';
                $intentGroups[$intentKey][] = $kw;
            }
            foreach ($intentGroups as $intentName => $group) {
                $pillarName = $group[0]->keyword;
                $cluster = self::buildCluster($pillarName, $group);
                $cluster->name = 'گروه ' . $intentName;
                $clusters[] = $cluster;
            }
        }

        // Sort clusters by strength descending
        usort($clusters, fn($a, $b) => $b->clusterStrength <=> $a->clusterStrength);

        // Assign cluster names back to keywords
        foreach ($clusters as $cluster) {
            foreach ($cluster->keywords as $kw) {
                $kw->cluster = $cluster->name;
            }
        }

        return $clusters;
    }

    private static function buildCluster(string $pillarName, array $keywords): ClusterData {
        $cluster = new ClusterData(
            name: $pillarName,
            pillarTopic: $pillarName,
            keywords: $keywords,
        );

        // Identify supporting articles and money pages
        foreach ($keywords as $kw) {
            if (in_array($kw->intent, [SearchIntent::Informational, SearchIntent::CommercialInvestigation])) {
                $cluster->supportingArticles[] = $kw->keyword;
            }
            if (in_array($kw->intent, [SearchIntent::Transactional, SearchIntent::Product])) {
                $cluster->moneyPages[] = $kw->keyword;
            }
        }

        // Calculate cluster strength
        $totalVolume = array_sum(array_map(fn($k) => $k->searchVolume, $keywords));
        $avgPriority = count($keywords) > 0
            ? array_sum(array_map(fn($k) => $k->priorityScore, $keywords)) / count($keywords)
            : 0;
        $sizeBonus = min(20, count($keywords) * 2);
        $cluster->clusterStrength = round(min(100, $avgPriority + $sizeBonus + ($totalVolume / 1000)), 1);

        // Generate suggested internal links
        $sorted = $keywords;
        usort($sorted, fn($a, $b) => $b->priorityScore <=> $a->priorityScore);
        $topKeywords = array_slice($sorted, 0, min(5, count($sorted)));
        foreach ($topKeywords as $kw) {
            $cluster->internalLinks[] = [
                'from' => $pillarName,
                'to' => $kw->keyword,
                'anchor' => $kw->keyword,
            ];
        }

        return $cluster;
    }

    /** @return string[] */
    private static function tokenize(string $text): array {
        $stopWords = ['و', 'در', 'با', 'از', 'به', 'برای', 'که', 'این', 'آن', 'را', 'تا', 'هم', 'یک', 'یا'];
        $words = explode(' ', mb_strtolower(TextNormalizer::normalize($text), 'UTF-8'));
        return array_values(array_filter($words, fn($w) => !in_array($w, $stopWords) && mb_strlen($w) > 1));
    }

    /** Check if two keywords share a significant root word */
    private static function hasSharedRoot(string $a, string $b): bool {
        $tokensA = self::tokenize($a);
        $tokensB = self::tokenize($b);
        foreach ($tokensA as $ta) {
            if (mb_strlen($ta) < 3) continue;
            foreach ($tokensB as $tb) {
                if (mb_strlen($tb) < 3) continue;
                // Check if one contains the other (root matching)
                if (str_contains($ta, $tb) || str_contains($tb, $ta)) {
                    return true;
                }
            }
        }
        return false;
    }
}

// ─── Content Strategy Generator ──────────────────────────────────────────────

final class ContentStrategyGenerator {
    /**
     * Generate content strategy recommendations for each keyword
     * @param KeywordData[] $keywords
     */
    public static function generate(array $keywords): void {
        foreach ($keywords as $kw) {
            $kw->pageType = self::recommendPageType($kw)->value;
            $kw->urlSlug = TextNormalizer::slugify($kw->keyword);
            $kw->suggestedH1 = self::generateH1($kw);
            $kw->metaTitle = self::generateMetaTitle($kw);
            $kw->contentType = self::recommendContentType($kw)->value;
            $kw->wordCount = self::suggestWordCount($kw);
            $kw->schemaMarkup = self::recommendSchema($kw);
            $kw->internalLinkTargets = self::suggestInternalLinks($kw, $keywords);
        }
    }

    private static function recommendPageType(KeywordData $kw): PageType {
        return match($kw->intent) {
            SearchIntent::Transactional => PageType::Product,
            SearchIntent::Product => PageType::Product,
            SearchIntent::Category => PageType::Category,
            SearchIntent::Informational => PageType::Blog,
            SearchIntent::CommercialInvestigation => PageType::Comparison,
            SearchIntent::Local => PageType::Local,
            SearchIntent::Navigational => PageType::Landing,
        };
    }

    private static function recommendContentType(KeywordData $kw): ContentType {
        $keyword = mb_strtolower($kw->keyword, 'UTF-8');
        return match(true) {
            str_contains($keyword, 'مقایسه') || str_contains($keyword, 'بهترین') => ContentType::ComparisonArticle,
            str_contains($keyword, 'آموزش') || str_contains($keyword, 'نحوه') || str_contains($keyword, 'چگونه') => ContentType::HowTo,
            str_contains($keyword, 'بررسی') || str_contains($keyword, 'نقد') => ContentType::Review,
            str_contains($keyword, 'انواع') || str_contains($keyword, 'لیست') => ContentType::Listicle,
            $kw->intent === SearchIntent::Product || $kw->intent === SearchIntent::Transactional => ContentType::ProductPage,
            $kw->intent === SearchIntent::Category => ContentType::CategoryPage,
            default => ContentType::Article,
        };
    }

    private static function generateH1(KeywordData $kw): string {
        return match($kw->intent) {
            SearchIntent::Informational => 'راهنمای جامع ' . $kw->keyword,
            SearchIntent::Transactional => $kw->keyword . ' | بهترین قیمت و ارسال سریع',
            SearchIntent::CommercialInvestigation => 'بهترین ' . $kw->keyword . ' در سال ۱۴۰۳',
            SearchIntent::Product => $kw->keyword . ' | مشخصات، قیمت و خرید',
            SearchIntent::Category => 'انواع ' . $kw->keyword . ' | مقایسه و راهنمای انتخاب',
            SearchIntent::Local => $kw->keyword . ' | آدرس، تلفن و نظرات',
            SearchIntent::Navigational => $kw->keyword,
        };
    }

    private static function generateMetaTitle(KeywordData $kw): string {
        $base = match($kw->intent) {
            SearchIntent::Informational => $kw->keyword . ' | راهنمای کامل ۱۴۰۳',
            SearchIntent::Transactional => '⭐ ' . $kw->keyword . ' | خرید با تخفیف ویژه',
            SearchIntent::CommercialInvestigation => 'بهترین ' . $kw->keyword . ' | مقایسه و بررسی ۱۴۰۳',
            SearchIntent::Product => $kw->keyword . ' | قیمت، مشخصات و نظرات',
            SearchIntent::Category => $kw->keyword . ' | دسته‌بندی و مقایسه محصولات',
            SearchIntent::Local => $kw->keyword . ' | نزدیک‌ترین مراکز',
            SearchIntent::Navigational => $kw->keyword . ' | صفحه رسمی',
        };
        return mb_substr($base, 0, 60, 'UTF-8');
    }

    private static function suggestWordCount(KeywordData $kw): int {
        return match($kw->intent) {
            SearchIntent::Informational => match($kw->difficulty) {
                Difficulty::Hard => 3500,
                Difficulty::Medium => 2500,
                Difficulty::Easy => 1500,
            },
            SearchIntent::CommercialInvestigation => match($kw->difficulty) {
                Difficulty::Hard => 3000,
                Difficulty::Medium => 2000,
                Difficulty::Easy => 1200,
            },
            SearchIntent::Product => 800,
            SearchIntent::Transactional => 600,
            SearchIntent::Category => 1500,
            SearchIntent::Local => 800,
            SearchIntent::Navigational => 500,
        };
    }

    private static function recommendSchema(KeywordData $kw): string {
        return match($kw->intent) {
            SearchIntent::Product, SearchIntent::Transactional => 'Product, Offer, AggregateRating',
            SearchIntent::Informational => 'Article, FAQPage, BreadcrumbList',
            SearchIntent::CommercialInvestigation => 'Article, ItemList, Review',
            SearchIntent::Category => 'ItemList, BreadcrumbList, CollectionPage',
            SearchIntent::Local => 'LocalBusiness, GeoCoordinates, Review',
            SearchIntent::Navigational => 'WebPage, BreadcrumbList',
        };
    }

    /**
     * Suggest internal link targets from other keywords in the set
     * @param KeywordData[] $allKeywords
     */
    private static function suggestInternalLinks(KeywordData $kw, array $allKeywords): string {
        $links = [];
        $kwTokens = explode(' ', mb_strtolower($kw->keyword, 'UTF-8'));

        foreach ($allKeywords as $other) {
            if ($other->keyword === $kw->keyword) continue;
            $otherTokens = explode(' ', mb_strtolower($other->keyword, 'UTF-8'));
            $overlap = count(array_intersect($kwTokens, $otherTokens));
            if ($overlap >= 1 && $other->priorityScore > 40) {
                $links[] = $other->keyword;
            }
            if (count($links) >= 3) break;
        }

        return implode(' | ', $links);
    }
}

// ─── Roadmap Generator ──────────────────────────────────────────────────────

final class RoadmapGenerator {
    /**
     * Categorize keywords into roadmap phases and generate timelines
     * @param KeywordData[] $keywords
     * @return array{quickWins: KeywordData[], mediumTerm: KeywordData[], longTerm: KeywordData[]}
     */
    public static function generate(array $keywords): array {
        $quickWins = [];
        $mediumTerm = [];
        $longTerm = [];

        foreach ($keywords as $kw) {
            if ($kw->difficulty === Difficulty::Easy && $kw->priorityScore >= 50) {
                $kw->roadmapPhase = 'برد سریع (۳ ماهه)';
                $quickWins[] = $kw;
            } elseif ($kw->difficulty === Difficulty::Medium || 
                     ($kw->difficulty === Difficulty::Easy && $kw->priorityScore < 50)) {
                $kw->roadmapPhase = 'میان‌مدت (۶ ماهه)';
                $mediumTerm[] = $kw;
            } else {
                $kw->roadmapPhase = 'بلندمدت (۱۲ ماهه)';
                $longTerm[] = $kw;
            }
        }

        // Sort each phase by priority
        usort($quickWins, fn($a, $b) => $b->priorityScore <=> $a->priorityScore);
        usort($mediumTerm, fn($a, $b) => $b->priorityScore <=> $a->priorityScore);
        usort($longTerm, fn($a, $b) => $b->priorityScore <=> $a->priorityScore);

        return compact('quickWins', 'mediumTerm', 'longTerm');
    }
}

// ─── Export Handlers ─────────────────────────────────────────────────────────

final class ExportHandler {
    /**
     * Export keywords as CSV
     * @param KeywordData[] $keywords
     */
    public static function exportCsv(array $keywords): void {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="seo-keywords-' . date('Y-m-d') . '.csv"');
        // BOM for Excel UTF-8 support
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        if ($out === false) return;

        fputcsv($out, [
            'کلمه کلیدی', 'حجم جستجو', 'سختی', 'اینتنت', 'قیف فروش',
            'ارزش تجاری', 'فرصت رتبه', 'پتانسیل ترافیک', 'پتانسیل درآمد',
            'فرصت SERP', 'اقتدار موضوعی', 'پیچیدگی محتوا', 'امتیاز اولویت',
            'خوشه', 'نوع صفحه', 'اسلاگ URL', 'H1 پیشنهادی', 'عنوان متا',
            'نوع محتوا', 'تعداد کلمات', 'اسکیما', 'لینک‌های داخلی', 'فاز نقشه‌راه',
        ]);

        foreach ($keywords as $kw) {
            fputcsv($out, [
                $kw->keyword, $kw->searchVolume, $kw->difficulty->value,
                $kw->intent?->value ?? '', $kw->funnel?->value ?? '',
                $kw->businessValue, $kw->rankingOpportunity, $kw->trafficPotential,
                $kw->revenuePotential, $kw->serpOpportunity, $kw->topicalAuthority,
                $kw->contentComplexity, $kw->priorityScore, $kw->cluster,
                $kw->pageType, $kw->urlSlug, $kw->suggestedH1, $kw->metaTitle,
                $kw->contentType, $kw->wordCount, $kw->schemaMarkup,
                $kw->internalLinkTargets, $kw->roadmapPhase,
            ]);
        }

        fclose($out);
    }

    /**
     * Export as Excel XML (compatible with all spreadsheet apps)
     * @param KeywordData[] $keywords
     */
    public static function exportExcel(array $keywords): void {
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="seo-keywords-' . date('Y-m-d') . '.xls"');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
        echo 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        echo '<Worksheet ss:Name="Keywords"><Table>' . "\n";

        // Header row
        $headers = [
            'کلمه کلیدی', 'حجم جستجو', 'سختی', 'اینتنت', 'قیف',
            'ارزش تجاری', 'فرصت رتبه', 'ترافیک', 'درآمد', 'SERP',
            'اقتدار', 'پیچیدگی', 'اولویت', 'خوشه', 'فاز',
        ];
        echo '<Row>';
        foreach ($headers as $h) {
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($h, ENT_XML1, 'UTF-8') . '</Data></Cell>';
        }
        echo '</Row>' . "\n";

        foreach ($keywords as $kw) {
            echo '<Row>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($kw->keyword, ENT_XML1, 'UTF-8') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $kw->searchVolume . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($kw->difficulty->value, ENT_XML1, 'UTF-8') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($kw->intent?->value ?? '', ENT_XML1, 'UTF-8') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($kw->funnel?->value ?? '', ENT_XML1, 'UTF-8') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $kw->businessValue . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $kw->rankingOpportunity . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $kw->trafficPotential . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $kw->revenuePotential . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $kw->serpOpportunity . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $kw->topicalAuthority . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $kw->contentComplexity . '</Data></Cell>';
            echo '<Cell><Data ss:Type="Number">' . $kw->priorityScore . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($kw->cluster, ENT_XML1, 'UTF-8') . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($kw->roadmapPhase, ENT_XML1, 'UTF-8') . '</Data></Cell>';
            echo '</Row>' . "\n";
        }

        echo '</Table></Worksheet></Workbook>';
    }

    /**
     * Export as JSON
     * @param KeywordData[] $keywords
     * @param ClusterData[] $clusters
     */
    public static function exportJson(array $keywords, array $clusters): void {
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="seo-keywords-' . date('Y-m-d') . '.json"');

        $data = [
            'meta' => [
                'app' => APP_NAME,
                'version' => APP_VERSION,
                'exported_at' => date('Y-m-d H:i:s'),
                'total_keywords' => count($keywords),
            ],
            'keywords' => array_map(fn(KeywordData $kw) => [
                'keyword' => $kw->keyword,
                'search_volume' => $kw->searchVolume,
                'difficulty' => $kw->difficulty->value,
                'intent' => $kw->intent?->value,
                'funnel' => $kw->funnel?->value,
                'scores' => [
                    'business_value' => $kw->businessValue,
                    'ranking_opportunity' => $kw->rankingOpportunity,
                    'traffic_potential' => $kw->trafficPotential,
                    'revenue_potential' => $kw->revenuePotential,
                    'serp_opportunity' => $kw->serpOpportunity,
                    'topical_authority' => $kw->topicalAuthority,
                    'content_complexity' => $kw->contentComplexity,
                    'priority_score' => $kw->priorityScore,
                ],
                'content_strategy' => [
                    'page_type' => $kw->pageType,
                    'url_slug' => $kw->urlSlug,
                    'h1' => $kw->suggestedH1,
                    'meta_title' => $kw->metaTitle,
                    'content_type' => $kw->contentType,
                    'word_count' => $kw->wordCount,
                    'schema' => $kw->schemaMarkup,
                    'internal_links' => $kw->internalLinkTargets,
                ],
                'cluster' => $kw->cluster,
                'roadmap_phase' => $kw->roadmapPhase,
            ], $keywords),
            'clusters' => array_map(fn(ClusterData $c) => [
                'name' => $c->name,
                'pillar_topic' => $c->pillarTopic,
                'keyword_count' => count($c->keywords),
                'strength' => $c->clusterStrength,
                'supporting_articles' => $c->supportingArticles,
                'money_pages' => $c->moneyPages,
                'internal_links' => $c->internalLinks,
            ], $clusters),
        ];

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

// ─── Dashboard Stats Calculator ──────────────────────────────────────────────

final class DashboardStats {
    public int $totalKeywords = 0;
    public float $avgDifficulty = 0;
    public int $totalVolume = 0;
    public int $quickWinsCount = 0;
    public int $revenueKeywords = 0;
    public float $estimatedTraffic = 0;
    public float $estimatedRevenue = 0;
    public int $totalClusters = 0;

    /** Distribution arrays for charts */
    public array $priorityDist = [];
    public array $intentDist = [];
    public array $difficultyDist = [];
    public array $funnelDist = [];
    public array $clusterSizeDist = [];

    /**
     * @param KeywordData[] $keywords
     * @param ClusterData[] $clusters
     * @param array $roadmap
     */
    public static function calculate(array $keywords, array $clusters, array $roadmap): self {
        $stats = new self();
        $stats->totalKeywords = count($keywords);
        $stats->totalClusters = count($clusters);
        $stats->quickWinsCount = count($roadmap['quickWins']);

        $diffSum = 0;
        $intentCounts = [];
        $difficultyCounts = ['آسان' => 0, 'متوسط' => 0, 'سخت' => 0];
        $funnelCounts = ['TOFU' => 0, 'MOFU' => 0, 'BOFU' => 0];
        $priorityBuckets = ['بالا (70-100)' => 0, 'متوسط (40-69)' => 0, 'پایین (0-39)' => 0];

        foreach ($keywords as $kw) {
            $stats->totalVolume += $kw->searchVolume;
            $diffSum += $kw->getDifficultyNumeric();

            // Intent distribution
            $intentKey = $kw->intent?->value ?? 'نامشخص';
            $intentCounts[$intentKey] = ($intentCounts[$intentKey] ?? 0) + 1;

            // Difficulty distribution
            $difficultyCounts[$kw->difficulty->value]++;

            // Funnel distribution
            $funnelKey = $kw->funnel?->value ?? 'TOFU';
            $funnelCounts[$funnelKey] = ($funnelCounts[$funnelKey] ?? 0) + 1;

            // Priority distribution
            if ($kw->priorityScore >= 70) $priorityBuckets['بالا (70-100)']++;
            elseif ($kw->priorityScore >= 40) $priorityBuckets['متوسط (40-69)']++;
            else $priorityBuckets['پایین (0-39)']++;

            // Revenue keywords
            if (in_array($kw->intent, [SearchIntent::Transactional, SearchIntent::Product])) {
                $stats->revenueKeywords++;
            }

            // Estimated traffic (CTR based on difficulty)
            $ctr = match($kw->difficulty) {
                Difficulty::Easy => 0.30,
                Difficulty::Medium => 0.12,
                Difficulty::Hard => 0.04,
            };
            $stats->estimatedTraffic += $kw->searchVolume * $ctr;

            // Estimated revenue
            $convRate = match($kw->intent) {
                SearchIntent::Transactional => 0.05,
                SearchIntent::Product => 0.04,
                default => 0.01,
            };
            $stats->estimatedRevenue += $kw->searchVolume * $ctr * $convRate * 500000;
        }

        $stats->avgDifficulty = $stats->totalKeywords > 0 ? round($diffSum / $stats->totalKeywords, 1) : 0;
        $stats->priorityDist = $priorityBuckets;
        $stats->intentDist = $intentCounts;
        $stats->difficultyDist = $difficultyCounts;
        $stats->funnelDist = $funnelCounts;

        // Cluster size distribution
        foreach ($clusters as $c) {
            $stats->clusterSizeDist[$c->name] = count($c->keywords);
        }

        return $stats;
    }
}

// ─── Main Application Controller ────────────────────────────────────────────

final class Application {
    /** @var KeywordData[] */
    private array $keywords = [];
    /** @var ClusterData[] */
    private array $clusters = [];
    private array $roadmap = ['quickWins' => [], 'mediumTerm' => [], 'longTerm' => []];
    private ?DashboardStats $stats = null;
    private bool $hasResults = false;
    private string $rawInput = '';

    public function run(): void {
        $this->rawInput = $_POST['keywords'] ?? '';

        // Handle export requests
        if (isset($_POST['export']) && isset($_SESSION['seo_keywords'])) {
            $this->keywords = unserialize($_SESSION['seo_keywords']);
            $this->clusters = unserialize($_SESSION['seo_clusters'] ?? serialize([]));
            $format = $_POST['export'];
            match($format) {
                'csv' => ExportHandler::exportCsv($this->keywords),
                'excel' => ExportHandler::exportExcel($this->keywords),
                'json' => ExportHandler::exportJson($this->keywords, $this->clusters),
                default => null,
            };
            exit;
        }

        // Process keyword input
        if (!empty($this->rawInput)) {
            $this->keywords = KeywordParser::parse($this->rawInput);

            if (!empty($this->keywords)) {
                // Run full analysis pipeline
                $this->keywords = SeoAnalysisEngine::analyze($this->keywords);
                $this->clusters = KeywordClusterer::cluster($this->keywords);
                ContentStrategyGenerator::generate($this->keywords);
                $this->roadmap = RoadmapGenerator::generate($this->keywords);
                $this->stats = DashboardStats::calculate($this->keywords, $this->clusters, $this->roadmap);
                $this->hasResults = true;

                // Store in session for export
                $_SESSION['seo_keywords'] = serialize($this->keywords);
                $_SESSION['seo_clusters'] = serialize($this->clusters);
            }
        }

        $this->render();
    }

    private function e(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function formatNumber(float $num): string {
        if ($num >= 1000000000) return round($num / 1000000000, 1) . 'B';
        if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
        if ($num >= 1000) return round($num / 1000, 1) . 'K';
        return (string)round($num);
    }

    private function render(): void {
        $app = $this;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $this->e(APP_NAME) ?> v<?= APP_VERSION ?></title>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
/* ─── CSS Reset & Variables ─────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg-primary: #0f1117;
    --bg-secondary: #1a1d2e;
    --bg-card: #1e2235;
    --bg-card-hover: #252a40;
    --bg-input: #151827;
    --border-color: #2a2f45;
    --border-hover: #3d4466;
    --text-primary: #e8eaf0;
    --text-secondary: #8b8fa3;
    --text-muted: #5a5f75;
    --accent-blue: #4f7cff;
    --accent-blue-hover: #6b93ff;
    --accent-purple: #7c5cfc;
    --accent-green: #22c55e;
    --accent-green-bg: rgba(34,197,94,0.12);
    --accent-orange: #f59e0b;
    --accent-orange-bg: rgba(245,158,11,0.12);
    --accent-red: #ef4444;
    --accent-red-bg: rgba(239,68,68,0.12);
    --accent-cyan: #06b6d4;
    --accent-pink: #ec4899;
    --gradient-blue: linear-gradient(135deg, #4f7cff, #7c5cfc);
    --gradient-green: linear-gradient(135deg, #22c55e, #06b6d4);
    --gradient-orange: linear-gradient(135deg, #f59e0b, #ef4444);
    --gradient-purple: linear-gradient(135deg, #7c5cfc, #ec4899);
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.4);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.5);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 20px;
    --transition: all 0.2s ease;
    font-family: 'Vazirmatn', 'Segoe UI', Tahoma, sans-serif;
}

@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap');

body {
    background: var(--bg-primary);
    color: var(--text-primary);
    font-family: 'Vazirmatn', sans-serif;
    line-height: 1.7;
    min-height: 100vh;
    direction: rtl;
}

/* ─── Scrollbar ─────────────────────────────────────────────────────── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--bg-primary); }
::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--border-hover); }

/* ─── Layout ────────────────────────────────────────────────────────── */
.container { max-width: 1440px; margin: 0 auto; padding: 20px; }

/* ─── Header ────────────────────────────────────────────────────────── */
.app-header {
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-color);
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    backdrop-filter: blur(12px);
}
.app-logo {
    display: flex;
    align-items: center;
    gap: 12px;
}
.app-logo-icon {
    width: 40px;
    height: 40px;
    background: var(--gradient-blue);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 800;
    color: white;
}
.app-logo-text {
    font-size: 20px;
    font-weight: 700;
    background: var(--gradient-blue);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.app-version {
    font-size: 11px;
    color: var(--text-muted);
    background: var(--bg-card);
    padding: 2px 8px;
    border-radius: 10px;
    margin-right: 8px;
}

/* ─── Input Section ─────────────────────────────────────────────────── */
.input-section {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 28px;
    margin: 24px 0;
    box-shadow: var(--shadow-md);
}
.input-section h2 {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 6px;
    color: var(--text-primary);
}
.input-section p {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 16px;
}
.input-section textarea {
    width: 100%;
    min-height: 200px;
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    color: var(--text-primary);
    padding: 16px;
    font-family: 'Vazirmatn', monospace;
    font-size: 14px;
    line-height: 1.8;
    resize: vertical;
    transition: var(--transition);
    direction: rtl;
}
.input-section textarea:focus {
    outline: none;
    border-color: var(--accent-blue);
    box-shadow: 0 0 0 3px rgba(79,124,255,0.15);
}
.input-section textarea::placeholder { color: var(--text-muted); }

.btn-row { display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap; }

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border: none;
    border-radius: var(--radius-sm);
    font-family: 'Vazirmatn', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
}
.btn-primary {
    background: var(--gradient-blue);
    color: white;
    box-shadow: 0 4px 14px rgba(79,124,255,0.3);
}
.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(79,124,255,0.4);
}
.btn-secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}
.btn-secondary:hover { border-color: var(--border-hover); background: var(--bg-card-hover); }
.btn-success {
    background: var(--gradient-green);
    color: white;
}
.btn-orange {
    background: var(--gradient-orange);
    color: white;
}

/* ─── KPI Cards Grid ────────────────────────────────────────────────── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin: 24px 0;
}
.kpi-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 20px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 3px;
}
.kpi-card:nth-child(1)::before { background: var(--gradient-blue); }
.kpi-card:nth-child(2)::before { background: var(--gradient-orange); }
.kpi-card:nth-child(3)::before { background: var(--gradient-green); }
.kpi-card:nth-child(4)::before { background: var(--gradient-purple); }
.kpi-card:nth-child(5)::before { background: var(--accent-pink); }
.kpi-card:nth-child(6)::before { background: var(--accent-cyan); }
.kpi-card:nth-child(7)::before { background: var(--accent-orange); }
.kpi-card:nth-child(8)::before { background: var(--accent-red); }
.kpi-card:hover { border-color: var(--border-hover); transform: translateY(-2px); box-shadow: var(--shadow-md); }
.kpi-label { font-size: 12px; color: var(--text-secondary); font-weight: 500; margin-bottom: 8px; }
.kpi-value { font-size: 28px; font-weight: 800; color: var(--text-primary); direction: ltr; text-align: right; }
.kpi-sub { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

/* ─── Section Headers ───────────────────────────────────────────────── */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 32px 0 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-color);
}
.section-header h2 {
    font-size: 18px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-header h2 span {
    font-size: 22px;
}

/* ─── Charts Grid ───────────────────────────────────────────────────── */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 20px;
    margin: 20px 0;
}
.chart-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 20px;
}
.chart-card h3 {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 16px;
}
.chart-card canvas { max-height: 280px; }

/* ─── Data Table ────────────────────────────────────────────────────── */
.table-container {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow: hidden;
    margin: 20px 0;
}
.table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
    flex-wrap: wrap;
    gap: 12px;
}
.table-toolbar input[type="text"] {
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    padding: 8px 14px;
    font-family: 'Vazirmatn', sans-serif;
    font-size: 13px;
    min-width: 240px;
    transition: var(--transition);
}
.table-toolbar input[type="text"]:focus {
    outline: none;
    border-color: var(--accent-blue);
}
.table-scroll { overflow-x: auto; }
table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    white-space: nowrap;
}
thead { background: var(--bg-secondary); }
th {
    padding: 12px 16px;
    text-align: right;
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border-color);
    cursor: pointer;
    user-select: none;
    position: sticky;
    top: 0;
    background: var(--bg-secondary);
}
th:hover { color: var(--accent-blue); }
td {
    padding: 10px 16px;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
}
tr:hover td { background: var(--bg-card-hover); }

/* ─── Badges ────────────────────────────────────────────────────────── */
.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.badge-easy { background: var(--accent-green-bg); color: var(--accent-green); }
.badge-medium { background: var(--accent-orange-bg); color: var(--accent-orange); }
.badge-hard { background: var(--accent-red-bg); color: var(--accent-red); }
.badge-blue { background: rgba(79,124,255,0.12); color: var(--accent-blue); }
.badge-purple { background: rgba(124,92,252,0.12); color: var(--accent-purple); }
.badge-cyan { background: rgba(6,182,212,0.12); color: var(--accent-cyan); }
.badge-pink { background: rgba(236,72,153,0.12); color: var(--accent-pink); }

/* ─── Progress Bar ──────────────────────────────────────────────────── */
.progress-bar {
    width: 100%;
    height: 6px;
    background: var(--bg-input);
    border-radius: 3px;
    overflow: hidden;
    position: relative;
}
.progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s ease;
}
.priority-high .progress-fill { background: var(--gradient-green); }
.priority-medium .progress-fill { background: var(--gradient-orange); }
.priority-low .progress-fill { background: var(--accent-red); }

.score-cell {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 120px;
}
.score-num { font-weight: 700; font-size: 14px; min-width: 32px; }
.score-high { color: var(--accent-green); }
.score-medium { color: var(--accent-orange); }
.score-low { color: var(--accent-red); }

/* ─── Cluster Cards ─────────────────────────────────────────────────── */
.cluster-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 16px;
    margin: 20px 0;
}
.cluster-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 20px;
    transition: var(--transition);
}
.cluster-card:hover { border-color: var(--border-hover); box-shadow: var(--shadow-md); }
.cluster-card h4 {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.cluster-meta {
    display: flex;
    gap: 16px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}
.cluster-meta span {
    font-size: 12px;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    gap: 4px;
}
.cluster-keywords {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}
.cluster-tag {
    background: var(--bg-input);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 3px 10px;
    font-size: 11px;
    color: var(--text-secondary);
}
.strength-bar {
    height: 4px;
    background: var(--bg-input);
    border-radius: 2px;
    margin-top: 12px;
    overflow: hidden;
}
.strength-fill {
    height: 100%;
    background: var(--gradient-blue);
    border-radius: 2px;
    transition: width 0.6s ease;
}

/* ─── Roadmap ───────────────────────────────────────────────────────── */
.roadmap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 20px;
    margin: 20px 0;
}
.roadmap-phase {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    overflow: hidden;
}
.roadmap-phase-header {
    padding: 16px 20px;
    font-weight: 700;
    font-size: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border-color);
}
.phase-quick .roadmap-phase-header { background: linear-gradient(135deg, rgba(34,197,94,0.1), transparent); color: var(--accent-green); }
.phase-medium .roadmap-phase-header { background: linear-gradient(135deg, rgba(245,158,11,0.1), transparent); color: var(--accent-orange); }
.phase-long .roadmap-phase-header { background: linear-gradient(135deg, rgba(239,68,68,0.1), transparent); color: var(--accent-red); }
.roadmap-item {
    padding: 10px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
}
.roadmap-item:last-child { border-bottom: none; }
.roadmap-item:hover { background: var(--bg-card-hover); }

/* ─── Tabs ──────────────────────────────────────────────────────────── */
.tabs {
    display: flex;
    gap: 4px;
    background: var(--bg-secondary);
    border-radius: var(--radius-sm);
    padding: 4px;
    margin: 20px 0;
    overflow-x: auto;
}
.tab-btn {
    padding: 8px 20px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: var(--text-secondary);
    font-family: 'Vazirmatn', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
}
.tab-btn:hover { color: var(--text-primary); }
.tab-btn.active { background: var(--accent-blue); color: white; }
.tab-content { display: none; }
.tab-content.active { display: block; }

/* ─── Content Strategy Table ────────────────────────────────────────── */
.strategy-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 16px 20px;
    margin: 8px 0;
    transition: var(--transition);
}
.strategy-card:hover { border-color: var(--border-hover); }
.strategy-card h4 { font-size: 14px; font-weight: 600; margin-bottom: 10px; color: var(--accent-blue); }
.strategy-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 8px;
    font-size: 12px;
}
.strategy-item { color: var(--text-secondary); }
.strategy-item strong { color: var(--text-primary); font-weight: 600; }

/* ─── Export Bar ────────────────────────────────────────────────────── */
.export-bar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* ─── Empty State ───────────────────────────────────────────────────── */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}
.empty-state-icon { font-size: 48px; margin-bottom: 16px; }
.empty-state h3 { font-size: 18px; font-weight: 600; color: var(--text-secondary); margin-bottom: 8px; }
.empty-state p { font-size: 13px; }

/* ─── Footer ────────────────────────────────────────────────────────── */
.app-footer {
    text-align: center;
    padding: 20px;
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 40px;
    border-top: 1px solid var(--border-color);
}

/* ─── Responsive ────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .container { padding: 12px; }
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .charts-grid { grid-template-columns: 1fr; }
    .cluster-grid { grid-template-columns: 1fr; }
    .roadmap-grid { grid-template-columns: 1fr; }
    .kpi-value { font-size: 22px; }
    .app-header { padding: 12px 16px; flex-wrap: wrap; gap: 8px; }
    .input-section { padding: 16px; }
    .table-toolbar { flex-direction: column; align-items: stretch; }
    .table-toolbar input[type="text"] { min-width: 100%; }
    .btn-row { flex-direction: column; }
    .btn { justify-content: center; }
}
@media (max-width: 480px) {
    .kpi-grid { grid-template-columns: 1fr; }
    .tabs { flex-direction: column; }
}
</style>
</head>
<body>

<!-- ─── Header ──────────────────────────────────────────────────────── -->
<header class="app-header">
    <div class="app-logo">
        <div class="app-logo-icon">SE</div>
        <div>
            <span class="app-logo-text"><?= $this->e(APP_NAME) ?></span>
            <span class="app-version">v<?= APP_VERSION ?></span>
        </div>
    </div>
    <?php if ($this->hasResults): ?>
    <div class="export-bar">
        <form method="post" style="display:inline">
            <input type="hidden" name="keywords" value="<?= $this->e($this->rawInput) ?>">
            <input type="hidden" name="export" value="csv">
            <button type="submit" class="btn btn-secondary">📥 CSV</button>
        </form>
        <form method="post" style="display:inline">
            <input type="hidden" name="keywords" value="<?= $this->e($this->rawInput) ?>">
            <input type="hidden" name="export" value="excel">
            <button type="submit" class="btn btn-secondary">📊 Excel</button>
        </form>
        <form method="post" style="display:inline">
            <input type="hidden" name="keywords" value="<?= $this->e($this->rawInput) ?>">
            <input type="hidden" name="export" value="json">
            <button type="submit" class="btn btn-secondary">🔧 JSON</button>
        </form>
    </div>
    <?php endif; ?>
</header>

<div class="container">

<!-- ─── Input Section ──────────────────────────────────────────────── -->
<section class="input-section">
    <h2>📋 ورود کلمات کلیدی</h2>
    <p>کلمات کلیدی را با فرمت <code>کلمه کلیدی | حجم جستجو | سختی</code> وارد کنید. هر کلمه در یک خط.</p>
    <form method="post" id="mainForm">
        <textarea name="keywords" placeholder="خرید صندل زنانه | 1000 | سخت
کفش ورزشی مردانه | 5000 | متوسط
آموزش سئو سایت | 3000 | آسان
بهترین لپ تاپ دانشجویی | 2000 | متوسط
قیمت آیفون ۱۵ | 8000 | سخت"
            ><?= $this->e($this->rawInput) ?></textarea>
        <div class="btn-row">
            <button type="submit" class="btn btn-primary">🚀 تحلیل کلمات کلیدی</button>
            <button type="button" class="btn btn-secondary" onclick="document.querySelector('textarea').value=''; document.querySelector('textarea').focus();">🗑️ پاک کردن</button>
            <button type="button" class="btn btn-secondary" onclick="loadSampleData()">📝 داده نمونه</button>
        </div>
    </form>
</section>

<?php if ($this->hasResults && $this->stats !== null): ?>

<!-- ─── KPI Dashboard ──────────────────────────────────────────────── -->
<div class="section-header">
    <h2><span>📊</span> داشبورد مدیریتی</h2>
</div>

<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-label">مجموع کلمات کلیدی</div>
        <div class="kpi-value"><?= number_format($this->stats->totalKeywords) ?></div>
        <div class="kpi-sub">کلمه تحلیل شده</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">میانگین سختی</div>
        <div class="kpi-value"><?= $this->stats->avgDifficulty ?></div>
        <div class="kpi-sub">از ۱۰۰</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">مجموع حجم جستجو</div>
        <div class="kpi-value"><?= $this->formatNumber($this->stats->totalVolume) ?></div>
        <div class="kpi-sub">جستجوی ماهانه</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">بردهای سریع</div>
        <div class="kpi-value"><?= number_format($this->stats->quickWinsCount) ?></div>
        <div class="kpi-sub">فرصت فوری</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">کلمات درآمدی</div>
        <div class="kpi-value"><?= number_format($this->stats->revenueKeywords) ?></div>
        <div class="kpi-sub">تراکنشی + محصولی</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">پتانسیل ترافیک</div>
        <div class="kpi-value"><?= $this->formatNumber($this->stats->estimatedTraffic) ?></div>
        <div class="kpi-sub">بازدید ماهانه تخمینی</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">پتانسیل درآمد</div>
        <div class="kpi-value"><?= $this->formatNumber($this->stats->estimatedRevenue) ?></div>
        <div class="kpi-sub">تومان / ماه</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-label">خوشه‌های موضوعی</div>
        <div class="kpi-value"><?= number_format($this->stats->totalClusters) ?></div>
        <div class="kpi-sub">گروه کلمات</div>
    </div>
</div>

<!-- ─── Charts ─────────────────────────────────────────────────────── -->
<div class="section-header">
    <h2><span>📈</span> نمودارها</h2>
</div>

<div class="charts-grid">
    <div class="chart-card">
        <h3>توزیع اولویت</h3>
        <canvas id="priorityChart"></canvas>
    </div>
    <div class="chart-card">
        <h3>توزیع اینتنت جستجو</h3>
        <canvas id="intentChart"></canvas>
    </div>
    <div class="chart-card">
        <h3>توزیع سختی</h3>
        <canvas id="difficultyChart"></canvas>
    </div>
    <div class="chart-card">
        <h3>توزیع قیف فروش</h3>
        <canvas id="funnelChart"></canvas>
    </div>
    <div class="chart-card">
        <h3>اندازه خوشه‌ها</h3>
        <canvas id="clusterChart"></canvas>
    </div>
</div>

<!-- ─── Tab Navigation ─────────────────────────────────────────────── -->
<div class="tabs" id="mainTabs">
    <button class="tab-btn active" onclick="switchTab('keywords')">📋 کلمات کلیدی</button>
    <button class="tab-btn" onclick="switchTab('clusters')">🔗 خوشه‌بندی</button>
    <button class="tab-btn" onclick="switchTab('strategy')">📝 استراتژی محتوا</button>
    <button class="tab-btn" onclick="switchTab('roadmap')">🗺️ نقشه راه</button>
</div>

<!-- ─── Tab: Keywords Table ────────────────────────────────────────── -->
<div class="tab-content active" id="tab-keywords">
    <div class="table-container">
        <div class="table-toolbar">
            <input type="text" id="searchInput" placeholder="🔍 جستجوی کلمه کلیدی..." onkeyup="filterTable()">
            <span style="color:var(--text-muted);font-size:12px"><?= count($this->keywords) ?> کلمه کلیدی</span>
        </div>
        <div class="table-scroll">
            <table id="keywordsTable">
                <thead>
                    <tr>
                        <th onclick="sortTable(0)">#</th>
                        <th onclick="sortTable(1)">کلمه کلیدی</th>
                        <th onclick="sortTable(2)">حجم جستجو</th>
                        <th onclick="sortTable(3)">سختی</th>
                        <th onclick="sortTable(4)">اینتنت</th>
                        <th onclick="sortTable(5)">قیف</th>
                        <th onclick="sortTable(6)">ارزش تجاری</th>
                        <th onclick="sortTable(7)">فرصت رتبه</th>
                        <th onclick="sortTable(8)">ترافیک</th>
                        <th onclick="sortTable(9)">درآمد</th>
                        <th onclick="sortTable(10)">SERP</th>
                        <th onclick="sortTable(11)">اولویت</th>
                        <th onclick="sortTable(12)">خوشه</th>
                        <th onclick="sortTable(13)">فاز</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($this->keywords as $i => $kw): ?>
                    <?php
                        $priorityClass = $kw->priorityScore >= 70 ? 'priority-high' : ($kw->priorityScore >= 40 ? 'priority-medium' : 'priority-low');
                        $scoreClass = $kw->priorityScore >= 70 ? 'score-high' : ($kw->priorityScore >= 40 ? 'score-medium' : 'score-low');
                        $diffBadge = match($kw->difficulty) {
                            Difficulty::Easy => 'badge-easy',
                            Difficulty::Medium => 'badge-medium',
                            Difficulty::Hard => 'badge-hard',
                        };
                        $intentBadge = match($kw->intent) {
                            SearchIntent::Transactional => 'badge-purple',
                            SearchIntent::CommercialInvestigation => 'badge-blue',
                            SearchIntent::Product => 'badge-pink',
                            default => 'badge-cyan',
                        };
                    ?>
                    <tr>
                        <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                        <td style="font-weight:600;max-width:200px;overflow:hidden;text-overflow:ellipsis"><?= $this->e($kw->keyword) ?></td>
                        <td style="direction:ltr;text-align:right"><?= number_format($kw->searchVolume) ?></td>
                        <td><span class="badge <?= $diffBadge ?>"><?= $this->e($kw->difficulty->value) ?></span></td>
                        <td><span class="badge <?= $intentBadge ?>"><?= $this->e($kw->intent?->value ?? '') ?></span></td>
                        <td><span class="badge badge-blue"><?= $this->e($kw->funnel?->value ?? '') ?></span></td>
                        <td><?= $kw->businessValue ?></td>
                        <td><?= $kw->rankingOpportunity ?></td>
                        <td><?= $kw->trafficPotential ?></td>
                        <td><?= $kw->revenuePotential ?></td>
                        <td><?= $kw->serpOpportunity ?></td>
                        <td>
                            <div class="score-cell">
                                <span class="score-num <?= $scoreClass ?>"><?= $kw->priorityScore ?></span>
                                <div class="progress-bar <?= $priorityClass ?>" style="flex:1">
                                    <div class="progress-fill" style="width:<?= $kw->priorityScore ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:11px;color:var(--text-secondary);max-width:120px;overflow:hidden;text-overflow:ellipsis"><?= $this->e($kw->cluster) ?></td>
                        <td style="font-size:11px"><?= $this->e($kw->roadmapPhase) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ─── Tab: Clusters ──────────────────────────────────────────────── -->
<div class="tab-content" id="tab-clusters">
    <div class="cluster-grid">
    <?php foreach (array_slice($this->clusters, 0, 20) as $cluster): ?>
        <div class="cluster-card">
            <h4>🔗 <?= $this->e($cluster->name) ?></h4>
            <div class="cluster-meta">
                <span>📄 <?= count($cluster->keywords) ?> کلمه</span>
                <span>💪 قدرت: <?= $cluster->clusterStrength ?></span>
                <span>📝 مقاله: <?= count($cluster->supportingArticles) ?></span>
                <span>💰 صفحه فروش: <?= count($cluster->moneyPages) ?></span>
            </div>
            <div><strong style="font-size:12px;color:var(--text-secondary)">موضوع ستونی:</strong> <span style="font-size:12px;color:var(--accent-blue)"><?= $this->e($cluster->pillarTopic) ?></span></div>
            
            <?php if (!empty($cluster->supportingArticles)): ?>
            <div style="margin-top:8px">
                <strong style="font-size:11px;color:var(--text-muted)">مقالات پشتیبان:</strong>
                <div class="cluster-keywords">
                    <?php foreach (array_slice($cluster->supportingArticles, 0, 5) as $article): ?>
                        <span class="cluster-tag"><?= $this->e($article) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($cluster->moneyPages)): ?>
            <div style="margin-top:6px">
                <strong style="font-size:11px;color:var(--text-muted)">صفحات فروش:</strong>
                <div class="cluster-keywords">
                    <?php foreach (array_slice($cluster->moneyPages, 0, 5) as $page): ?>
                        <span class="cluster-tag" style="border-color:var(--accent-purple)"><?= $this->e($page) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($cluster->internalLinks)): ?>
            <div style="margin-top:6px">
                <strong style="font-size:11px;color:var(--text-muted)">لینک‌های داخلی پیشنهادی:</strong>
                <div class="cluster-keywords">
                    <?php foreach (array_slice($cluster->internalLinks, 0, 3) as $link): ?>
                        <span class="cluster-tag" style="border-color:var(--accent-cyan)"><?= $this->e($link['from']) ?> ← <?= $this->e($link['to']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="strength-bar">
                <div class="strength-fill" style="width:<?= min(100, $cluster->clusterStrength) ?>%"></div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- ─── Tab: Content Strategy ──────────────────────────────────────── -->
<div class="tab-content" id="tab-strategy">
    <?php foreach (array_slice($this->keywords, 0, 50) as $kw): ?>
    <div class="strategy-card">
        <h4><?= $this->e($kw->keyword) ?> <span class="badge badge-blue" style="font-size:10px;margin-right:8px"><?= $kw->priorityScore ?> امتیاز</span></h4>
        <div class="strategy-grid">
            <div class="strategy-item"><strong>نوع صفحه:</strong> <?= $this->e($kw->pageType) ?></div>
            <div class="strategy-item"><strong>اسلاگ URL:</strong> <code style="color:var(--accent-green);font-size:11px;direction:ltr;display:inline-block">/{<?= $this->e($kw->urlSlug) ?>}</code></div>
            <div class="strategy-item"><strong>H1 پیشنهادی:</strong> <?= $this->e($kw->suggestedH1) ?></div>
            <div class="strategy-item"><strong>عنوان متا:</strong> <?= $this->e($kw->metaTitle) ?></div>
            <div class="strategy-item"><strong>نوع محتوا:</strong> <?= $this->e($kw->contentType) ?></div>
            <div class="strategy-item"><strong>تعداد کلمات:</strong> <?= number_format($kw->wordCount) ?> کلمه</div>
            <div class="strategy-item"><strong>اینتنت:</strong> <?= $this->e($kw->intent?->value ?? '') ?></div>
            <div class="strategy-item"><strong>اسکیما:</strong> <span style="color:var(--accent-cyan);font-size:11px"><?= $this->e($kw->schemaMarkup) ?></span></div>
            <?php if (!empty($kw->internalLinkTargets)): ?>
            <div class="strategy-item" style="grid-column:span 2"><strong>لینک‌های داخلی:</strong> <?= $this->e($kw->internalLinkTargets) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (count($this->keywords) > 50): ?>
        <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px">
            ... و <?= count($this->keywords) - 50 ?> کلمه کلیدی دیگر (برای مشاهده همه، خروجی JSON دانلود کنید)
        </div>
    <?php endif; ?>
</div>

<!-- ─── Tab: Roadmap ───────────────────────────────────────────────── -->
<div class="tab-content" id="tab-roadmap">
    <div class="roadmap-grid">
        <!-- Quick Wins - 3 Month -->
        <div class="roadmap-phase phase-quick">
            <div class="roadmap-phase-header">
                <span>⚡ بردهای سریع — ۳ ماهه</span>
                <span class="badge badge-easy"><?= count($this->roadmap['quickWins']) ?> کلمه</span>
            </div>
            <?php foreach (array_slice($this->roadmap['quickWins'], 0, 15) as $kw): ?>
            <div class="roadmap-item">
                <span><?= $this->e($kw->keyword) ?></span>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:11px;color:var(--text-muted)"><?= number_format($kw->searchVolume) ?></span>
                    <span class="score-num score-high" style="font-size:12px"><?= $kw->priorityScore ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($this->roadmap['quickWins']) > 15): ?>
                <div class="roadmap-item" style="justify-content:center;color:var(--text-muted)">+ <?= count($this->roadmap['quickWins']) - 15 ?> مورد دیگر</div>
            <?php endif; ?>
        </div>

        <!-- Medium Term - 6 Month -->
        <div class="roadmap-phase phase-medium">
            <div class="roadmap-phase-header">
                <span>📈 میان‌مدت — ۶ ماهه</span>
                <span class="badge badge-medium"><?= count($this->roadmap['mediumTerm']) ?> کلمه</span>
            </div>
            <?php foreach (array_slice($this->roadmap['mediumTerm'], 0, 15) as $kw): ?>
            <div class="roadmap-item">
                <span><?= $this->e($kw->keyword) ?></span>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:11px;color:var(--text-muted)"><?= number_format($kw->searchVolume) ?></span>
                    <span class="score-num score-medium" style="font-size:12px"><?= $kw->priorityScore ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($this->roadmap['mediumTerm']) > 15): ?>
                <div class="roadmap-item" style="justify-content:center;color:var(--text-muted)">+ <?= count($this->roadmap['mediumTerm']) - 15 ?> مورد دیگر</div>
            <?php endif; ?>
        </div>

        <!-- Long Term - 12 Month -->
        <div class="roadmap-phase phase-long">
            <div class="roadmap-phase-header">
                <span>🎯 بلندمدت — ۱۲ ماهه</span>
                <span class="badge badge-hard"><?= count($this->roadmap['longTerm']) ?> کلمه</span>
            </div>
            <?php foreach (array_slice($this->roadmap['longTerm'], 0, 15) as $kw): ?>
            <div class="roadmap-item">
                <span><?= $this->e($kw->keyword) ?></span>
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="font-size:11px;color:var(--text-muted)"><?= number_format($kw->searchVolume) ?></span>
                    <span class="score-num score-low" style="font-size:12px"><?= $kw->priorityScore ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($this->roadmap['longTerm']) > 15): ?>
                <div class="roadmap-item" style="justify-content:center;color:var(--text-muted)">+ <?= count($this->roadmap['longTerm']) - 15 ?> مورد دیگر</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ─── Empty State ────────────────────────────────────────────────── -->
<div class="empty-state">
    <div class="empty-state-icon">🔍</div>
    <h3>کلمات کلیدی خود را وارد کنید</h3>
    <p>کلمات کلیدی را در فرمت مشخص شده وارد کرده و دکمه تحلیل را بزنید</p>
</div>
<?php endif; ?>

</div><!-- /container -->

<!-- ─── Footer ─────────────────────────────────────────────────────── -->
<footer class="app-footer">
    <?= $this->e(APP_NAME) ?> v<?= APP_VERSION ?> — موتور اولویت‌بندی کلمات کلیدی سئو | PHP <?= PHP_VERSION ?>
</footer>

<!-- ─── JavaScript ─────────────────────────────────────────────────── -->
<script>
// Tab switching
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    const target = document.getElementById('tab-' + tab);
    if (target) target.classList.add('active');
    event.target.classList.add('active');
}

// Table search/filter
function filterTable() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#keywordsTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

// Table sorting
let sortDir = {};
function sortTable(col) {
    const table = document.getElementById('keywordsTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    sortDir[col] = !sortDir[col];
    const dir = sortDir[col] ? 1 : -1;

    rows.sort((a, b) => {
        let valA = a.cells[col]?.textContent?.trim() || '';
        let valB = b.cells[col]?.textContent?.trim() || '';
        const numA = parseFloat(valA.replace(/[^0-9.-]/g, ''));
        const numB = parseFloat(valB.replace(/[^0-9.-]/g, ''));
        if (!isNaN(numA) && !isNaN(numB)) return (numA - numB) * dir;
        return valA.localeCompare(valB, 'fa') * dir;
    });

    rows.forEach(row => tbody.appendChild(row));
}

// Sample data loader
function loadSampleData() {
    const sample = `خرید صندل زنانه | 1000 | سخت
کفش ورزشی مردانه | 5000 | متوسط
آموزش سئو سایت | 3000 | آسان
بهترین لپ تاپ دانشجویی | 2000 | متوسط
قیمت آیفون ۱۵ | 8000 | سخت
خرید کتاب آنلاین | 1500 | آسان
بهترین هاست ایرانی | 900 | متوسط
آموزش پایتون رایگان | 4000 | آسان
قیمت طلا امروز | 12000 | سخت
خرید عطر زنانه اصل | 600 | متوسط
مقایسه گوشی سامسونگ و شیائومی | 1800 | متوسط
بهترین دوربین عکاسی | 700 | سخت
آموزش طراحی سایت | 2500 | آسان
خرید ساعت هوشمند | 3200 | سخت
نحوه افزایش رتبه سایت | 1100 | آسان
فروشگاه اینترنتی لوازم آشپزخانه | 450 | متوسط
بهترین نرم افزار حسابداری | 800 | متوسط
قیمت خودرو پژو ۲۰۶ | 6000 | سخت
آموزش زبان انگلیسی | 9000 | سخت
خرید لباس زنانه ارزان | 2200 | متوسط
نقد و بررسی مک‌بوک پرو | 500 | سخت
انواع پاور بانک | 1300 | آسان
راهنمای خرید تلویزیون | 750 | متوسط
فروش آنلاین قهوه | 400 | آسان
تفاوت SSD و HDD | 2000 | آسان`;
    document.querySelector('textarea').value = sample;
}

<?php if ($this->hasResults && $this->stats !== null): ?>
// ─── Chart.js Initialization ────────────────────────────────────────
Chart.defaults.color = '#8b8fa3';
Chart.defaults.font.family = 'Vazirmatn, sans-serif';
Chart.defaults.plugins.legend.rtl = true;
Chart.defaults.plugins.legend.labels.usePointStyle = true;

const chartColors = {
    blue: '#4f7cff',
    purple: '#7c5cfc',
    green: '#22c55e',
    orange: '#f59e0b',
    red: '#ef4444',
    cyan: '#06b6d4',
    pink: '#ec4899',
    blueBg: 'rgba(79,124,255,0.7)',
    purpleBg: 'rgba(124,92,252,0.7)',
    greenBg: 'rgba(34,197,94,0.7)',
    orangeBg: 'rgba(245,158,11,0.7)',
    redBg: 'rgba(239,68,68,0.7)',
    cyanBg: 'rgba(6,182,212,0.7)',
    pinkBg: 'rgba(236,72,153,0.7)',
};

// Priority Distribution (Doughnut)
new Chart(document.getElementById('priorityChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($this->stats->priorityDist), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            data: <?= json_encode(array_values($this->stats->priorityDist)) ?>,
            backgroundColor: [chartColors.greenBg, chartColors.orangeBg, chartColors.redBg],
            borderColor: ['#1e2235','#1e2235','#1e2235'],
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        },
        cutout: '65%',
    }
});

// Intent Distribution (Polar Area)
new Chart(document.getElementById('intentChart'), {
    type: 'polarArea',
    data: {
        labels: <?= json_encode(array_keys($this->stats->intentDist), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            data: <?= json_encode(array_values($this->stats->intentDist)) ?>,
            backgroundColor: [
                chartColors.blueBg, chartColors.purpleBg, chartColors.greenBg,
                chartColors.orangeBg, chartColors.redBg, chartColors.cyanBg, chartColors.pinkBg,
            ],
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { r: { grid: { color: '#2a2f45' }, ticks: { display: false } } }
    }
});

// Difficulty Distribution (Bar)
new Chart(document.getElementById('difficultyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($this->stats->difficultyDist), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            label: 'تعداد',
            data: <?= json_encode(array_values($this->stats->difficultyDist)) ?>,
            backgroundColor: [chartColors.greenBg, chartColors.orangeBg, chartColors.redBg],
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { grid: { color: '#2a2f45' }, beginAtZero: true }
        }
    }
});

// Funnel Distribution (Doughnut)
new Chart(document.getElementById('funnelChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($this->stats->funnelDist), JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            data: <?= json_encode(array_values($this->stats->funnelDist)) ?>,
            backgroundColor: [chartColors.cyanBg, chartColors.orangeBg, chartColors.purpleBg],
            borderColor: ['#1e2235','#1e2235','#1e2235'],
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        cutout: '65%',
    }
});

// Cluster Size Distribution (Horizontal Bar)
const clusterLabels = <?= json_encode(array_keys(array_slice($this->stats->clusterSizeDist, 0, 10, true)), JSON_UNESCAPED_UNICODE) ?>;
const clusterData = <?= json_encode(array_values(array_slice($this->stats->clusterSizeDist, 0, 10, true))) ?>;
new Chart(document.getElementById('clusterChart'), {
    type: 'bar',
    data: {
        labels: clusterLabels.map(l => l.length > 20 ? l.substring(0, 20) + '...' : l),
        datasets: [{
            label: 'تعداد کلمات',
            data: clusterData,
            backgroundColor: chartColors.blueBg,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: '#2a2f45' }, beginAtZero: true },
            y: { grid: { display: false } }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>
<?php
    }
}

// ─── Bootstrap ───────────────────────────────────────────────────────────────
session_start();
$app = new Application();
$app->run();
