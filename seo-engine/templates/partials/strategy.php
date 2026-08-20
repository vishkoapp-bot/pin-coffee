<?php
/**
 * @var SeoEngine\Application $app
 * @var SeoEngine\Models\KeywordData[] $keywords
 */
?>
<div class="tab-content" id="tab-st" role="tabpanel" aria-labelledby="tab-btn-st">
    <?php foreach (array_slice($keywords, 0, 50) as $kw): ?>
    <article class="strategy-card">
        <h3 class="strategy-card__title">
            <?= $app->e($kw->keyword) ?>
            <span class="badge badge--funnel" style="font-size: 10px; margin-right: 6px">
                <?= $kw->priorityScore ?> امتیاز
            </span>
        </h3>
        <div class="strategy-grid">
            <div class="strategy-item"><strong>نوع صفحه:</strong> <?= $app->e($kw->pageType) ?></div>
            <div class="strategy-item"><strong>URL:</strong> <code>/<?= $app->e($kw->urlSlug) ?></code></div>
            <div class="strategy-item"><strong>H1:</strong> <?= $app->e($kw->suggestedH1) ?></div>
            <div class="strategy-item"><strong>متا:</strong> <?= $app->e($kw->metaTitle) ?></div>
            <div class="strategy-item"><strong>نوع محتوا:</strong> <?= $app->e($kw->contentType) ?></div>
            <div class="strategy-item"><strong>کلمات:</strong> <?= number_format($kw->wordCount) ?></div>
            <div class="strategy-item"><strong>اینتنت:</strong> <?= $app->e($kw->intent) ?></div>
            <div class="strategy-item"><strong>اسکیما:</strong> <span style="color: var(--text-sub); font-size: 12px"><?= $app->e($kw->schemaMarkup) ?></span></div>
            <?php if ($kw->internalLinkTargets): ?>
            <div class="strategy-item" style="grid-column: span 2">
                <strong>لینک داخلی:</strong> <?= $app->e($kw->internalLinkTargets) ?>
            </div>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
    <?php if (count($keywords) > 50): ?>
    <p class="section-sub" style="padding: 10px 0; text-align: center;">
        ... و <?= count($keywords) - 50 ?> کلمه دیگر (دانلود JSON)
    </p>
    <?php endif; ?>
</div>
