<?php
/**
 * @var SeoEngine\Application $app
 */
use SeoEngine\Config;
?>
<div class="topbar-inner">
    <div class="logo" aria-label="SEO Keyword Engine">
        <div class="logo__icon">SE</div>
        <div>
            <div class="logo__text"><?= $app->e(Config::APP_NAME) ?></div>
            <div class="logo__version">v<?= Config::APP_VERSION ?></div>
        </div>
    </div>
    <div class="header-actions" aria-label="عملیات خروجی">
        <?php if ($app->hasResults()): ?>
            <?php foreach (['csv' => 'دانلود CSV', 'excel' => 'دانلود Excel', 'json' => 'دانلود JSON'] as $fmt => $label): ?>
            <form method="post" class="export-form">
                <input type="hidden" name="keywords" value="<?= $app->e($app->getRawInput()) ?>">
                <input type="hidden" name="export" value="<?= $fmt ?>">
                <button type="submit" class="btn btn--secondary"><?= $label ?></button>
            </form>
            <?php endforeach; ?>
        <?php else: ?>
            <span class="hero-stat">آماده دریافت داده شما</span>
        <?php endif; ?>
    </div>
</div>
