<?php
/**
 * @var SeoEngine\Application $app
 * @var array $roadmap
 */
?>
<div class="tab-content" id="tab-rm" role="tabpanel" aria-labelledby="tab-btn-rm">
    <div class="roadmap-grid">
        <?php
        $phases = [
            ['quickWins',  'roadmap-phase--quick',  '⚡ بردهای سریع — ۳ ماهه', 'badge--easy',   'score--high'],
            ['mediumTerm', 'roadmap-phase--medium', '📈 میان‌مدت — ۶ ماهه',    'badge--medium', 'score--medium'],
            ['longTerm',   'roadmap-phase--long',   '🎯 بلندمدت — ۱۲ ماهه',   'badge--hard',   'score--low'],
        ];
        foreach ($phases as [$key, $phaseClass, $title, $badgeClass, $scoreClass]): ?>
        <section class="roadmap-phase <?= $phaseClass ?>">
            <header class="roadmap-phase__header">
                <span><?= $title ?></span>
                <span class="badge <?= $badgeClass ?>"><?= count($roadmap[$key]) ?> کلمه</span>
            </header>
            <?php foreach (array_slice($roadmap[$key], 0, 15) as $kw): ?>
            <div class="roadmap-item">
                <span><?= $app->e($kw->keyword) ?></span>
                <div class="roadmap-item__info">
                    <span class="roadmap-item__meta"><?= number_format($kw->searchVolume) ?></span>
                    <span class="score-number <?= $scoreClass ?>"><?= $kw->priorityScore ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($roadmap[$key]) > 15): ?>
            <div class="roadmap-item roadmap-item--more">
                + <?= count($roadmap[$key]) - 15 ?> مورد دیگر
            </div>
            <?php endif; ?>
        </section>
        <?php endforeach; ?>
    </div>
</div>
