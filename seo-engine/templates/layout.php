<?php
/**
 * Main layout template for SEO Keyword Engine v3.0
 *
 * @var SeoEngine\Application $app
 */
use SeoEngine\Config;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $app->e(Config::APP_NAME) ?> v<?= Config::APP_VERSION ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>
<a href="#main-content" class="skip-link">پرش به محتوای اصلی</a>

<header class="topbar app-topbar">
    <?php include __DIR__ . '/partials/header.php'; ?>
</header>

<main id="main-content" class="container page-wrap">
    <?php include __DIR__ . '/partials/input-form.php'; ?>

    <?php if ($app->hasResults() && $app->getStats()): ?>
        <?php
        $stats    = $app->getStats();
        $keywords = $app->getKeywords();
        $clusters = $app->getClusters();
        $roadmap  = $app->getRoadmap();
        ?>

        <section class="panel section-shell">
            <header class="section-header">
                <div class="section-title"><span aria-hidden="true">📊</span><h2>داشبورد مدیریتی</h2></div>
                <div class="section-sub">نمای سریع شاخص‌های کلیدی تحلیل</div>
            </header>
            <?php include __DIR__ . '/partials/dashboard.php'; ?>
        </section>

        <section class="panel section-shell">
            <header class="section-header">
                <div class="section-title"><span aria-hidden="true">📈</span><h2>نمودارها</h2></div>
                <div class="section-sub">توزیع اولویت، سختی، intent و خوشه‌ها</div>
            </header>
            <?php include __DIR__ . '/partials/charts.php'; ?>
        </section>

        <section class="panel section-shell">
            <div class="tab-bar" id="mainTabs" role="tablist" aria-label="بخش‌های تحلیل">
                <button class="tab active" role="tab" aria-selected="true" aria-controls="tab-kw" id="tab-btn-kw" onclick="switchTab('kw', this)">کلمات کلیدی</button>
                <button class="tab" role="tab" aria-selected="false" aria-controls="tab-cl" id="tab-btn-cl" onclick="switchTab('cl', this)">خوشه‌بندی</button>
                <button class="tab" role="tab" aria-selected="false" aria-controls="tab-st" id="tab-btn-st" onclick="switchTab('st', this)">استراتژی محتوا</button>
                <button class="tab" role="tab" aria-selected="false" aria-controls="tab-rm" id="tab-btn-rm" onclick="switchTab('rm', this)">نقشه راه</button>
            </div>

            <?php include __DIR__ . '/partials/keywords-table.php'; ?>
            <?php include __DIR__ . '/partials/clusters.php'; ?>
            <?php include __DIR__ . '/partials/strategy.php'; ?>
            <?php include __DIR__ . '/partials/roadmap.php'; ?>
        </section>
    <?php else: ?>
        <section class="panel section-shell">
            <div class="empty-state">
                <div class="empty-state__icon">🔎</div>
                <h3>داده‌ای برای نمایش وجود ندارد</h3>
                <p>کلمات کلیدی را وارد کنید تا جدول، نمودارها و نقشه راه تحلیل ساخته شود.</p>
            </div>
        </section>
    <?php endif; ?>
</main>

<footer class="footer">
    <?= $app->e(Config::APP_NAME) ?> v<?= Config::APP_VERSION ?>
</footer>

<script src="assets/js/app.js"></script>
<?php if ($app->hasResults() && $app->getStats()): ?>
<?php include __DIR__ . '/partials/charts-init.php'; ?>
<?php endif; ?>

</body>
</html>
