<?php
/**
 * @var SeoEngine\Application $app
 * @var SeoEngine\Models\DashboardStats $stats
 */
?>
<div class="kpi-grid" role="list" aria-label="نمای آماری">
<?php
$kpis = [
    ['مجموع کلمات کلیدی', number_format($stats->totalKeywords), 'کلمه تحلیل‌شده'],
    ['میانگین سختی', (string)$stats->avgDifficulty, 'از ۱۰۰'],
    ['مجموع حجم جست‌وجو', $app->formatNumber($stats->totalVolume), 'جستجوی ماهانه'],
    ['بردهای سریع', number_format($stats->quickWinsCount), 'فرصت فوری'],
    ['کلمات درآمدی', number_format($stats->revenueKeywords), 'تراکنشی + محصولی'],
    ['پتانسیل ترافیک', $app->formatNumber($stats->estimatedTraffic), 'بازدید ماهانه تخمینی'],
    ['پتانسیل درآمد', $app->formatNumber($stats->estimatedRevenue), 'تومان / ماه'],
    ['خوشه‌های موضوعی', number_format($stats->totalClusters), 'گروه کلمات'],
];
foreach ($kpis as $kpi): ?>
    <article class="kpi-card">
        <div class="kpi-card__label"><?= $kpi[0] ?></div>
        <div class="kpi-card__value"><?= $kpi[1] ?></div>
        <div class="kpi-card__sub"><?= $kpi[2] ?></div>
    </article>
<?php endforeach; ?>
</div>
