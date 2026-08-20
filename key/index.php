<?php
declare(strict_types=1);

$rawInput = $_POST['keywords'] ?? <<<TEXT
صندل زنانه | 5000 | خیلی سخت
خرید صندل زنانه ارزان | 250 | معمولی
صندل طبی زنانه | 1200 | سخت
قیمت صندل زنانه | 800 | سخت
TEXT;

$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';

function normalizeText(string $text): string
{
    $text = trim($text);
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    return $text;
}

function parseKeywords(string $input): array
{
    $lines = preg_split('/\n+/', normalizeText($input)) ?: [];
    $rows = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (str_contains($line, '|')) {
            $parts = array_values(array_filter(array_map('trim', explode('|', $line)), static fn($v) => $v !== ''));
        } elseif (str_contains($line, ',')) {
            $parts = array_values(array_filter(array_map('trim', str_getcsv($line)), static fn($v) => $v !== ''));
        } else {
            $parts = preg_split('/\s+/', $line) ?: [];
        }

        if (count($parts) < 3) {
            continue;
        }

        $keyword = (string)$parts[0];
        $volume = (int)preg_replace('/[^\d]/u', '', (string)$parts[1]);
        $difficulty = trim((string)$parts[2]);

        if ($keyword === '' || $volume <= 0 || $difficulty === '') {
            continue;
        }

        $rows[] = [
            'keyword' => $keyword,
            'volume' => $volume,
            'difficulty_label' => $difficulty,
        ];
    }

    return $rows;
}

function difficultyWeight(string $difficulty): int
{
    return match ($difficulty) {
        'معمولی' => 1,
        'سخت' => 2,
        'خیلی سخت' => 3,
        default => 2,
    };
}

function detectIntent(string $keyword): array
{
    $kw = mb_strtolower($keyword, 'UTF-8');
    $transactionalTerms = ['خرید', 'قیمت', 'ارزان', 'سفارش', 'فروش', 'برند', 'برندها', 'تخفیف', 'موجود'];
    $informationalTerms = ['راهنمای', 'مدل', 'بهترین', 'چگونه', 'چیست', 'مزایا', 'معایب', 'نحوه'];

    foreach ($transactionalTerms as $term) {
        if (mb_strpos($kw, $term, 0, 'UTF-8') !== false) {
            return ['Commercial / Transactional', 3, 'Transaction / category'];
        }
    }

    foreach ($informationalTerms as $term) {
        if (mb_strpos($kw, $term, 0, 'UTF-8') !== false) {
            return ['Informational', 1, 'Blog Article'];
        }
    }

    $words = preg_split('/\s+/u', trim($kw)) ?: [];
    if (count($words) <= 2) {
        return ['Category / Product', 2, 'Category Page'];
    }

    return ['Category / Product', 2, 'Landing Page'];
}

function businessValueScore(string $intent, string $keyword): int
{
    $kw = mb_strtolower($keyword, 'UTF-8');
    if (str_contains($kw, 'خرید') || str_contains($kw, 'قیمت') || str_contains($kw, 'ارزان') || str_contains($kw, 'سفارش')) {
        return 5;
    }
    if ($intent === 'Commercial / Transactional') {
        return 5;
    }
    if ($intent === 'Category / Product') {
        return 4;
    }
    if (str_contains($kw, 'بهترین') || str_contains($kw, 'راهنمای')) {
        return 3;
    }
    return 2;
}

function pageTypeForIntent(string $intent, string $keyword): string
{
    $kw = mb_strtolower($keyword, 'UTF-8');
    if (str_contains($kw, 'خرید') || str_contains($kw, 'قیمت') || str_contains($kw, 'ارزان') || str_contains($kw, 'سفارش')) {
        return 'Product Listing Page';
    }
    return match ($intent) {
        'Commercial / Transactional' => 'Category Page',
        'Category / Product' => 'Category Page',
        'Informational' => 'Blog Article',
        default => 'Landing Page',
    };
}

function isLongTail(string $keyword): bool
{
    $words = preg_split('/\s+/u', trim($keyword)) ?: [];
    return count($words) >= 4;
}

function rankingProbability(int $volume, int $difficultyWeight, string $intent, string $keyword): float
{
    $base = 100;
    $base -= ($difficultyWeight - 1) * 28;
    $base += match ($intent) {
        'Commercial / Transactional' => 10,
        'Category / Product' => 5,
        default => 0,
    };

    if (isLongTail($keyword)) {
        $base += 12;
    }

    if ($volume <= 300) {
        $base += 10;
    } elseif ($volume <= 1000) {
        $base += 4;
    } else {
        $base -= 6;
    }

    if (preg_match('/\b(خرید|قیمت|ارزان|سفارش)\b/u', $keyword)) {
        $base += 8;
    }

    if ($difficultyWeight === 3 && !$thisIsLongTail = isLongTail($keyword)) {
        $base -= 12;
    }

    return max(0, min(100, (float)$base));
}

function topicalAuthorityScore(string $intent, string $keyword, int $volume): float
{
    $score = 50;
    if ($intent === 'Category / Product') {
        $score += 20;
    } elseif ($intent === 'Commercial / Transactional') {
        $score += 15;
    } else {
        $score -= 10;
    }

    if (isLongTail($keyword)) {
        $score += 12;
    }

    if ($volume >= 1000) {
        $score += 5;
    }

    if (preg_match('/\b(بهترین|راهنمای|مدل)\b/u', $keyword)) {
        $score += 6;
    }

    return max(0, min(100, (float)$score));
}

function basePriority(int $volume, int $intentWeight, int $difficultyWeight): float
{
    return ($volume * $intentWeight) / max(1, $difficultyWeight);
}

function finalScore(float $basePriority, float $rankingProbability, float $topicalAuthority, int $businessValue, string $keyword): float
{
    $normalizedBase = min(100, $basePriority / 50);
    $score = ($normalizedBase * 22) + ($businessValue * 10) + ($rankingProbability * 0.35) + ($topicalAuthority * 0.25);

    if (isLongTail($keyword)) {
        $score += 8;
    }

    if (preg_match('/\b(خرید|قیمت|ارزان|سفارش)\b/u', $keyword)) {
        $score += 10;
    }

    if (preg_match('/\b(صندل|کفش|لباس|گوشی|لپ تاپ|مبلمان|ساعت)\b/u', $keyword) && !preg_match('/\b(خرید|قیمت|ارزان|سفارش)\b/u', $keyword)) {
        $score -= 6;
    }

    if ($rankingProbability < 40) {
        $score -= 8;
    }

    return max(0, min(100, round($score, 2)));
}

function phaseForScore(float $score): array
{
    return match (true) {
        $score >= 80 => ['فاز 1', 'Quick Wins', 'green-dark'],
        $score >= 65 => ['فاز 2', 'اولویت بالا', 'green'],
        $score >= 50 => ['فاز 3', 'اولویت متوسط', 'yellow'],
        $score >= 35 => ['فاز 4', 'اولویت پایین', 'orange'],
        default => ['فاز 5', 'بلندمدت', 'red'],
    };
}

function clusterName(string $keyword, string $intent): string
{
    $kw = mb_strtolower($keyword, 'UTF-8');
    if (str_contains($kw, 'خرید') || str_contains($kw, 'قیمت') || str_contains($kw, 'ارزان') || str_contains($kw, 'سفارش')) {
        return 'Commercial Cluster';
    }
    if (str_contains($kw, 'راهنمای') || str_contains($kw, 'بهترین') || str_contains($kw, 'مدل')) {
        return 'Informational Cluster';
    }
    if ($intent === 'Category / Product') {
        return 'Category Cluster';
    }
    return 'General Cluster';
}

$rows = [];
$dashboard = [
    'total' => 0,
    'avgDifficulty' => 0,
    'quickWins' => 0,
    'revenueKeywords' => 0,
];

if ($submitted) {
    $parsed = parseKeywords($rawInput);
    $dashboard['total'] = count($parsed);

    $difficultySum = 0;
    foreach ($parsed as $item) {
        [$intentLabel, $intentWeight, $pageHint] = detectIntent($item['keyword']);
        $diffWeight = difficultyWeight($item['difficulty_label']);
        $businessValue = businessValueScore($intentLabel, $item['keyword']);
        $base = basePriority($item['volume'], $intentWeight, $diffWeight);
        $rankProb = rankingProbability($item['volume'], $diffWeight, $intentLabel, $item['keyword']);
        $topical = topicalAuthorityScore($intentLabel, $item['keyword'], $item['volume']);
        $score = finalScore($base, $rankProb, $topical, $businessValue, $item['keyword']);
        [$phaseLabel, $phaseDesc, $phaseColor] = phaseForScore($score);
        $pageType = pageTypeForIntent($intentLabel, $item['keyword']);
        $cluster = clusterName($item['keyword'], $intentLabel);
        $difficultySum += $diffWeight;

        if ($score >= 80) {
            $dashboard['quickWins']++;
        }
        if ($intentWeight >= 3) {
            $dashboard['revenueKeywords']++;
        }

        $rows[] = [
            'keyword' => $item['keyword'],
            'volume' => $item['volume'],
            'difficulty' => $item['difficulty_label'],
            'difficulty_weight' => $diffWeight,
            'intent' => $intentLabel,
            'intent_weight' => $intentWeight,
            'business_value' => $businessValue,
            'base' => round($base, 2),
            'ranking' => round($rankProb, 2),
            'authority' => round($topical, 2),
            'score' => $score,
            'phase' => $phaseLabel . ' - ' . $phaseDesc,
            'phase_color' => $phaseColor,
            'page_type' => $pageType,
            'cluster' => $cluster,
        ];
    }

    $dashboard['avgDifficulty'] = $dashboard['total'] > 0 ? round($difficultySum / $dashboard['total'], 2) : 0;

    usort($rows, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
}

$clusters = [];
foreach ($rows as $row) {
    $clusters[$row['cluster']][] = $row;
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Keyword Priority Engine</title>
    <style>
        :root {
            --bg: #0b1220;
            --panel: #111a2e;
            --panel-2: #16233d;
            --text: #eef2ff;
            --muted: #a7b3d1;
            --border: rgba(255,255,255,.08);
            --accent: #5eead4;
            --accent-2: #60a5fa;
            --green-dark: #0f766e;
            --green: #22c55e;
            --yellow: #f59e0b;
            --orange: #fb923c;
            --red: #ef4444;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Tahoma, Arial, sans-serif;
            background: radial-gradient(circle at top right, rgba(96,165,250,.18), transparent 35%),
                        radial-gradient(circle at left, rgba(94,234,212,.15), transparent 30%),
                        var(--bg);
            color: var(--text);
        }
        .wrap { max-width: 1400px; margin: 0 auto; padding: 28px 16px 56px; }
        .hero {
            background: linear-gradient(135deg, rgba(17,26,46,.96), rgba(22,35,61,.92));
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
        }
        h1 { margin: 0 0 8px; font-size: 2rem; }
        p { color: var(--muted); line-height: 1.8; }
        textarea {
            width: 100%; min-height: 220px; resize: vertical;
            border-radius: 18px; border: 1px solid var(--border);
            background: rgba(8,15,28,.75); color: var(--text);
            padding: 16px; font-size: 15px; line-height: 1.8;
            outline: none;
        }
        .actions { display:flex; gap:12px; flex-wrap:wrap; margin-top: 16px; }
        button {
            border: 0; border-radius: 14px; padding: 12px 18px;
            background: linear-gradient(135deg, var(--accent-2), var(--accent));
            color: #06111f; font-weight: 700; cursor: pointer;
        }
        .grid {
            display:grid; grid-template-columns: repeat(4, minmax(0,1fr));
            gap: 14px; margin-top: 18px;
        }
        .card {
            background: rgba(17,26,46,.92);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 16px;
        }
        .card .label { color: var(--muted); font-size: .92rem; }
        .card .value { font-size: 1.6rem; font-weight: 800; margin-top: 8px; }
        .section { margin-top: 28px; }
        table { width: 100%; border-collapse: collapse; overflow: hidden; }
        th, td {
            border-bottom: 1px solid var(--border);
            padding: 12px 10px; text-align: right; vertical-align: top;
        }
        th { color: #dbeafe; font-size: .92rem; background: rgba(255,255,255,.03); }
        .table-wrap {
            overflow-x: auto;
            background: rgba(17,26,46,.82);
            border: 1px solid var(--border);
            border-radius: 18px;
        }
        .tag {
            display:inline-block; padding: 6px 10px; border-radius: 999px;
            font-size: .82rem; font-weight: 700;
        }
        .green-dark { background: rgba(15,118,110,.25); color: #5eead4; }
        .green { background: rgba(34,197,94,.18); color: #86efac; }
        .yellow { background: rgba(245,158,11,.18); color: #fde68a; }
        .orange { background: rgba(251,146,60,.18); color: #fdba74; }
        .red { background: rgba(239,68,68,.18); color: #fca5a5; }
        .cluster {
            background: rgba(17,26,46,.82);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 16px;
            margin-bottom: 14px;
        }
        .cluster h3 { margin: 0 0 10px; }
        .muted { color: var(--muted); }
        .insight-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 14px; }
        ul { margin: 8px 0 0; padding-right: 20px; }
        li { margin: 8px 0; line-height: 1.8; }
        @media (max-width: 900px) {
            .grid, .insight-grid { grid-template-columns: 1fr; }
            h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hero">
        <h1>SEO Keyword Priority Engine</h1>
        <p>کلمات کلیدی را با فرمت <strong>کلمه کلیدی | سرچ ماهانه | سختی</strong> وارد کن. سیستم بر اساس intent، سختی، احتمال رتبه‌گیری، ارزش تجاری و topical authority خروجی می‌دهد.</p>

        <form method="post">
            <textarea name="keywords" placeholder="صندل زنانه | 5000 | خیلی سخت&#10;خرید صندل زنانه ارزان | 250 | معمولی"><?=h($rawInput)?></textarea>
            <div class="actions">
                <button type="submit">تحلیل و اولویت‌بندی</button>
            </div>
        </form>
    </div>

    <?php if ($submitted): ?>
        <div class="grid section">
            <div class="card"><div class="label">Total Keywords</div><div class="value"><?=number_format((int)$dashboard['total'])?></div></div>
            <div class="card"><div class="label">Average Difficulty</div><div class="value"><?=h((string)$dashboard['avgDifficulty'])?></div></div>
            <div class="card"><div class="label">Quick Wins Count</div><div class="value"><?=number_format((int)$dashboard['quickWins'])?></div></div>
            <div class="card"><div class="label">Revenue Keywords Count</div><div class="value"><?=number_format((int)$dashboard['revenueKeywords'])?></div></div>
        </div>

        <div class="section table-wrap">
            <table>
                <thead>
                <tr>
                    <th>رتبه</th>
                    <th>کلمه کلیدی</th>
                    <th>سرچ</th>
                    <th>سختی</th>
                    <th>Intent</th>
                    <th>Business Value</th>
                    <th>Page Type</th>
                    <th>Ranking Probability</th>
                    <th>Topical Authority</th>
                    <th>Score</th>
                    <th>فاز</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $index => $row): ?>
                    <tr>
                        <td><?=number_format($index + 1)?></td>
                        <td><strong><?=h($row['keyword'])?></strong><div class="muted"><?=h($row['cluster'])?></div></td>
                        <td><?=number_format((int)$row['volume'])?></td>
                        <td><?=h($row['difficulty'])?></td>
                        <td><?=h($row['intent'])?></td>
                        <td><?=number_format((int)$row['business_value'])?></td>
                        <td><?=h($row['page_type'])?></td>
                        <td><?=h((string)$row['ranking'])?></td>
                        <td><?=h((string)$row['authority'])?></td>
                        <td><strong><?=h((string)$row['score'])?></strong></td>
                        <td><span class="tag <?=h($row['phase_color'])?>"><?=h($row['phase'])?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Semantic Clusters</h2>
            <?php foreach ($clusters as $clusterName => $clusterRows): ?>
                <div class="cluster">
                    <h3><?=h($clusterName)?></h3>
                    <div class="muted"><?=count($clusterRows)?> keyword(s)</div>
                    <ul>
                        <?php foreach ($clusterRows as $row): ?>
                            <li><?=h($row['keyword'])?> - Score <?=h((string)$row['score'])?> - <?=h($row['page_type'])?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section insight-grid">
            <div class="cluster">
                <h3>Quick Wins</h3>
                <ul>
                    <?php foreach (array_slice(array_filter($rows, static fn($r) => $r['score'] >= 80), 0, 5) as $row): ?>
                        <li><?=h($row['keyword'])?> - <?=h((string)$row['score'])?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="cluster">
                <h3>Mid-Term Opportunities</h3>
                <ul>
                    <?php foreach (array_slice(array_filter($rows, static fn($r) => $r['score'] >= 50 && $r['score'] < 80), 0, 5) as $row): ?>
                        <li><?=h($row['keyword'])?> - <?=h((string)$row['score'])?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="cluster">
                <h3>Long-Term Targets</h3>
                <ul>
                    <?php foreach (array_slice(array_filter($rows, static fn($r) => $r['score'] < 50), 0, 5) as $row): ?>
                        <li><?=h($row['keyword'])?> - <?=h((string)$row['score'])?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="cluster">
                <h3>Suggested Page Structure</h3>
                <ul>
                    <?php foreach (array_slice($rows, 0, 8) as $row): ?>
                        <li><?=h($row['keyword'])?> - <?=h($row['page_type'])?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="section cluster">
            <h3>6-Month SEO Roadmap</h3>
            <ul>
                <li>ماه 1: انتشار و بهینه‌سازی سریع‌ترین کلمات با Score بالای 80 و intent خرید.</li>
                <li>ماه 2: ساخت صفحات Category/Listing و پوشش semantic clusterهای اصلی.</li>
                <li>ماه 3: افزودن محتوای Helpful Content و FAQ برای کلمات نیمه‌رقابتی.</li>
                <li>ماه 4: تقویت internal linking بین صفحات money page و محتوای پشتیبان.</li>
                <li>ماه 5: هدف‌گیری long-tail های مرحله 3 و 4 با صفحاتی با اتوریتی بیشتر.</li>
                <li>ماه 6: ورود به head terms و کلمات خیلی سخت با پشتوانه topical authority.</li>
            </ul>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
