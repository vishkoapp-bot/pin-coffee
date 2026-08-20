<?php
/**
 * @var SeoEngine\Application $app
 * @var SeoEngine\Models\KeywordData[] $keywords
 */
use SeoEngine\Models\Difficulty;
use SeoEngine\Models\SearchIntent;
?>
<div class="tab-content active" id="tab-kw" role="tabpanel" aria-labelledby="tab-btn-kw">
    <div class="table-toolbar">
        <div class="toolbar-left">
            <input class="input" type="text" id="searchInput" placeholder="🔍 جستجوی کلمه کلیدی..." onkeyup="filterTable()" aria-label="فیلتر کلمات کلیدی">
            <span class="toolbar-count" aria-live="polite"><?= count($keywords) ?> کلمه</span>
        </div>
        <div class="toolbar-right">
            <button class="btn btn--ghost" type="button" onclick="filterBy('all')">نمایش همه</button>
            <button class="btn btn--ghost" type="button" onclick="filterBy('easy')">آسان</button>
            <button class="btn btn--ghost" type="button" onclick="filterBy('commercial')">درآمدی</button>
            <button class="btn btn--ghost" type="button" onclick="filterBy('funnelTop')">قیف بالا</button>
        </div>
    </div>
    <div class="table-wrapper">
        <div class="table-scroll">
            <table id="keywordTable" aria-label="جدول کلمات کلیدی">
                <thead>
                <tr>
                    <th scope="col" onclick="sortTable(0)">#</th>
                    <th scope="col" onclick="sortTable(1)">کلمه کلیدی</th>
                    <th scope="col" onclick="sortTable(2)">حجم</th>
                    <th scope="col" onclick="sortTable(3)">سختی</th>
                    <th scope="col" onclick="sortTable(4)">اینتنت</th>
                    <th scope="col" onclick="sortTable(5)">قیف</th>
                    <th scope="col" onclick="sortTable(6)">ارزش</th>
                    <th scope="col" onclick="sortTable(7)">فرصت</th>
                    <th scope="col" onclick="sortTable(8)">ترافیک</th>
                    <th scope="col" onclick="sortTable(9)">درآمد</th>
                    <th scope="col" onclick="sortTable(10)">SERP</th>
                    <th scope="col" onclick="sortTable(11)">اولویت</th>
                    <th scope="col" onclick="sortTable(12)">خوشه</th>
                    <th scope="col" onclick="sortTable(13)">فاز</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($keywords as $index => $kw):
                    $priorityClass = $kw->getPriorityClass();
                    $diffBadge = match($kw->difficulty) {
                        Difficulty::Easy   => 'badge--easy',
                        Difficulty::Medium => 'badge--medium',
                        default            => 'badge--hard',
                    };
                    $intentBadge = SearchIntent::isRevenue($kw->intent) ? 'badge--commercial' : 'badge--info';
                    $rowTags = [
                        'difficulty:' . strtolower((string)$kw->difficulty),
                        'funnel:' . strtolower((string)$kw->funnel),
                        SearchIntent::isRevenue($kw->intent) ? 'commercial' : 'noncommercial'
                    ];
                ?>
                <tr data-tags="<?= implode(' ', array_map('htmlspecialchars', $rowTags)) ?>">
                    <td style="color: var(--text-muted)"><?= $index + 1 ?></td>
                    <td style="font-weight: 600; max-width: 200px; overflow: hidden; text-overflow: ellipsis"><?= $app->e($kw->keyword) ?></td>
                    <td style="direction: ltr; text-align: right">
                        <?= number_format($kw->searchVolume) ?>
                    </td>
                    <td><span class="badge <?= $diffBadge ?>"><?= $app->e($kw->difficulty) ?></span></td>
                    <td><span class="badge <?= $intentBadge ?>"><?= $app->e($kw->intent) ?></span></td>
                    <td><span class="badge badge--funnel"><?= $app->e($kw->funnel) ?></span></td>
                    <td><?= $kw->businessValue ?></td>
                    <td><?= $kw->rankingOpportunity ?></td>
                    <td><?= $kw->trafficPotential ?></td>
                    <td><?= $kw->revenuePotential ?></td>
                    <td><?= $kw->serpOpportunity ?></td>
                    <td>
                        <div class="score-cell">
                            <span class="score-number score--<?= $priorityClass ?>"><?= $kw->priorityScore ?></span>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $kw->priorityScore ?>%"></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size: 11px; color: var(--text-sub); max-width: 120px; overflow: hidden; text-overflow: ellipsis"><?= $app->e($kw->cluster) ?></td>
                    <td style="font-size: 11px"><?= $app->e($kw->roadmapPhase) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
