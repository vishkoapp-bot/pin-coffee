<?php
/**
 * @var SeoEngine\Application $app
 * @var SeoEngine\Models\ClusterData[] $clusters
 */
?>
<div class="tab-content" id="tab-cl" role="tabpanel" aria-labelledby="tab-btn-cl">
    <div class="cluster-grid">
        <?php foreach ($clusters as $cluster): ?>
        <article class="cluster-card">
            <h3 class="cluster-card__title">
                🔗 <?= $app->e($cluster->name) ?>
            </h3>
            <div class="cluster-card__meta">
                <span>📊 <?= count($cluster->keywords) ?> کلمه</span>
                <span>🔍 <?= number_format($cluster->getTotalVolume()) ?> حجم</span>
                <span>⚡ <?= $cluster->getAveragePriority() ?> میانگین اولویت</span>
            </div>

            <?php if (!empty($cluster->supportingArticles)): ?>
            <div class="cluster-section">
                <div class="cluster-section__title">مقالات پشتیبان</div>
                <div class="cluster-tags">
                    <?php foreach (array_slice($cluster->supportingArticles, 0, 5) as $article): ?>
                    <span class="cluster-tag"><?= $app->e($article) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($cluster->moneyPages)): ?>
            <div class="cluster-section">
                <div class="cluster-section__title">صفحات فروش</div>
                <div class="cluster-tags">
                    <?php foreach (array_slice($cluster->moneyPages, 0, 5) as $page): ?>
                    <span class="cluster-tag"><?= $app->e($page) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($cluster->internalLinks)): ?>
            <div class="cluster-section">
                <div class="cluster-section__title">لینک‌های داخلی</div>
                <div class="cluster-tags">
                    <?php foreach (array_slice($cluster->internalLinks, 0, 3) as $link): ?>
                    <span class="cluster-tag">
                        <?= $app->e($link['from']) ?> ← <?= $app->e($link['to']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="strength-bar" aria-hidden="true">
                <div class="strength-fill" style="width: <?= min(100, $cluster->clusterStrength) ?>%"></div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</div>
