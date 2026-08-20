<?php
/**
 * Chart.js initialization script (rendered only when results exist).
 *
 * @var SeoEngine\Models\DashboardStats $stats
 */
?>
<script>
(function() {
    var ink = '#1f2937';
    var soft = '#526071';
    var grid = 'rgba(100, 116, 139, 0.16)';
    var paper = '#f8fafc';

    Chart.defaults.color = soft;
    Chart.defaults.font.family = 'Vazirmatn, Pelak, Estedad, Inter, sans-serif';
    Chart.defaults.font.size = 12;
    Chart.defaults.borderColor = grid;
    Chart.defaults.devicePixelRatio = Math.min(window.devicePixelRatio || 1, 2);
    Chart.defaults.plugins.legend.rtl = true;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.boxHeight = 8;
    Chart.defaults.plugins.legend.labels.padding = 16;
    Chart.defaults.plugins.tooltip.rtl = true;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(17, 24, 39, 0.94)';
    Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
    Chart.defaults.plugins.tooltip.bodyColor = '#f8fafc';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.displayColors = true;

    var colors = {
        emerald: '#10b981',
        amber: '#f59e0b',
        rose: '#f43f5e',
        blue: '#2563eb',
        cyan: '#06b6d4',
        violet: '#8b5cf6',
        zinc: '#71717a',
        lime: '#84cc16'
    };

    function alpha(hex, value) {
        var r = parseInt(hex.slice(1, 3), 16);
        var g = parseInt(hex.slice(3, 5), 16);
        var b = parseInt(hex.slice(5, 7), 16);
        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + value + ')';
    }

    function gradient(ctx, from, to) {
        var chart = ctx.chart;
        var area = chart.chartArea;
        if (!area) return from;
        var g = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
        g.addColorStop(0, alpha(from, .94));
        g.addColorStop(1, alpha(to, .62));
        return g;
    }

    function chartNode(id) {
        return document.getElementById(id);
    }

    var ringOptions = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 720, easing: 'easeOutQuart' },
        interaction: { mode: 'nearest', intersect: false },
        plugins: {
            legend: { position: 'bottom', labels: { color: soft } },
            tooltip: { callbacks: { label: function(item) { return ' ' + item.label + ': ' + item.formattedValue; } } }
        },
        cutout: '66%'
    };

    new Chart(chartNode('chartPriority'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($stats->priorityDist), JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode(array_values($stats->priorityDist)) ?>,
                backgroundColor: [colors.emerald, colors.amber, colors.rose],
                borderColor: paper,
                borderWidth: 3,
                hoverOffset: 8,
                spacing: 2
            }]
        },
        options: ringOptions
    });

    new Chart(chartNode('chartIntent'), {
        type: 'polarArea',
        data: {
            labels: <?= json_encode(array_keys($stats->intentDist), JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode(array_values($stats->intentDist)) ?>,
                backgroundColor: [
                    alpha(colors.blue, .74), alpha(colors.cyan, .70), alpha(colors.violet, .70),
                    alpha(colors.amber, .70), alpha(colors.rose, .66), alpha(colors.lime, .68), alpha(colors.zinc, .62)
                ],
                borderColor: paper,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 720, easing: 'easeOutQuart' },
            plugins: { legend: { position: 'bottom' } },
            scales: { r: { grid: { color: grid }, angleLines: { color: grid }, ticks: { display: false } } }
        }
    });

    new Chart(chartNode('chartDifficulty'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($stats->difficultyDist), JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'تعداد',
                data: <?= json_encode(array_values($stats->difficultyDist)) ?>,
                backgroundColor: function(ctx) {
                    var palette = [colors.emerald, colors.amber, colors.rose];
                    return gradient(ctx, palette[ctx.dataIndex] || colors.blue, palette[ctx.dataIndex] || colors.cyan);
                },
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 54
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 680, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: ink, font: { weight: 700 } } },
                y: { grid: { color: grid }, beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    new Chart(chartNode('chartFunnel'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($stats->funnelDist), JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode(array_values($stats->funnelDist)) ?>,
                backgroundColor: [colors.cyan, colors.amber, colors.violet],
                borderColor: paper,
                borderWidth: 3,
                hoverOffset: 8,
                spacing: 2
            }]
        },
        options: ringOptions
    });

    var clusterLabels = <?= json_encode(array_keys(array_slice($stats->clusterSizeDist, 0, 10, true)), JSON_UNESCAPED_UNICODE) ?>;
    var clusterData = <?= json_encode(array_values(array_slice($stats->clusterSizeDist, 0, 10, true))) ?>;

    new Chart(chartNode('chartClusters'), {
        type: 'bar',
        data: {
            labels: clusterLabels.map(function(label) { return label.length > 18 ? label.substring(0, 18) + '...' : label; }),
            datasets: [{
                label: 'تعداد کلمات',
                data: clusterData,
                backgroundColor: function(ctx) { return gradient(ctx, colors.blue, colors.cyan); },
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 26
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 720, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: grid }, beginAtZero: true, ticks: { precision: 0 } },
                y: { grid: { display: false }, ticks: { color: ink, font: { weight: 700 } } }
            }
        }
    });
})();
</script>
