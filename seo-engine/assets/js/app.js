/**
 * SEO Keyword Engine v3.0 — Client-side interactions
 */

(function() {
    'use strict';

    function normalizeText(value) {
        return (value || '').toLowerCase();
    }

    function isTopFunnel(text) {
        var f = normalizeText(text);
        return f.indexOf('top') !== -1 || f.indexOf('آگاهی') !== -1 || f.indexOf('آگاه') !== -1 || f.indexOf('consideration') !== -1;
    }

    function setActiveTab(button) {
        document.querySelectorAll('.tab').forEach(function(el) {
            el.classList.toggle('active', el === button);
            el.setAttribute('aria-selected', el === button ? 'true' : 'false');
        });
    }

    function hideAllTabPanels() {
        document.querySelectorAll('.tab-content').forEach(function(panel) {
            panel.classList.remove('active');
            panel.hidden = true;
        });
    }

    function activatePanel(panelId) {
        hideAllTabPanels();
        var target = document.getElementById('tab-' + panelId);
        if (!target) return;
        target.hidden = false;
        target.classList.add('active');
    }

    function switchTab(tabId, button) {
        activatePanel(tabId);
        setActiveTab(button);
        var liveRegion = document.getElementById('tabLiveRegion');
        if (liveRegion) {
            var title = button ? button.textContent.trim() : '';
            liveRegion.textContent = title ? 'بخش فعال شد: ' + title : '';
        }
    }

    function filterTable() {
        var input = document.getElementById('searchInput');
        if (!input) return;
        var query = normalizeText(input.value);
        var rows = document.querySelectorAll('#keywordTable tbody tr');
        rows.forEach(function(row) {
            var text = normalizeText(row.textContent);
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    var sortDirections = {};

    function parseNumeric(value) {
        return parseFloat((value || '').replace(/[^0-9.\-]/g, ''));
    }

    function sortTable(colIndex) {
        var table = document.getElementById('keywordTable');
        if (!table) return;
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));

        sortDirections[colIndex] = !sortDirections[colIndex];
        var direction = sortDirections[colIndex] ? 1 : -1;

        rows.sort(function(rowA, rowB) {
            var cellA = rowA.cells[colIndex];
            var cellB = rowB.cells[colIndex];
            if (!cellA || !cellB) return 0;

            var valA = cellA.textContent.trim();
            var valB = cellB.textContent.trim();
            var numA = parseNumeric(valA);
            var numB = parseNumeric(valB);

            if (!isNaN(numA) && !isNaN(numB)) {
                return (numA - numB) * direction;
            }
            return valA.localeCompare(valB, 'fa') * direction;
        });

        rows.forEach(function(row) {
            tbody.appendChild(row);
        });
    }

    function filterBy(mode) {
        var rows = document.querySelectorAll('#keywordTable tbody tr');
        rows.forEach(function(row) {
            var tags = normalizeText(row.getAttribute('data-tags') || '');
            var funnel = normalizeText(row.children[5] ? row.children[5].textContent : '');

            if (mode === 'all') {
                row.style.display = '';
                return;
            }

            if (mode === 'easy') {
                row.style.display = tags.indexOf('difficulty:آسان') !== -1 ? '' : 'none';
                return;
            }

            if (mode === 'commercial') {
                row.style.display = tags.indexOf('commercial') !== -1 ? '' : 'none';
                return;
            }

            if (mode === 'funnelTop') {
                row.style.display = isTopFunnel(funnel) ? '' : 'none';
                return;
            }

            row.style.display = '';
        });

        var counter = document.querySelector('.toolbar-count');
        if (counter) {
            var visible = Array.from(document.querySelectorAll('#keywordTable tbody tr')).filter(function(row) {
                return row.style.display !== 'none';
            }).length;
            counter.textContent = visible + ' کلمه نمایش داده‌شده';
        }
    }

    function loadSample() {
        var textarea = document.querySelector('textarea#keywordsInput');
        if (!textarea) return;
        textarea.value =
            "خرید صندل زنانه | 1000 | سخت\n" +
            "کفش ورزشی مردانه | 5000 | متوسط\n" +
            "آموزش سئو سایت | 3000 | آسان\n" +
            "بهترین لپ تاپ دانشجویی | 2000 | متوسط\n" +
            "قیمت آیفون ۱۵ | 8000 | سخت\n" +
            "خرید کتاب آنلاین | 1500 | آسان\n" +
            "بهترین هاست ایرانی | 900 | متوسط\n" +
            "آموزش پایتون رایگان | 4000 | آسان\n" +
            "قیمت طلا امروز | 12000 | سخت\n" +
            "خرید عطر زنانه اصل | 600 | متوسط\n" +
            "مقایسه گوشی سامسونگ و شیائومی | 1800 | متوسط\n" +
            "بهترین دوربین عکاسی | 700 | سخت\n" +
            "آموزش طراحی سایت | 2500 | آسان\n" +
            "خرید ساعت هوشمند | 3200 | سخت\n" +
            "نحوه افزایش رتبه سایت | 1100 | آسان\n" +
            "فروشگاه اینترنتی لوازم آشپزخانه | 450 | متوسط\n" +
            "بهترین نرم افزار حسابداری | 800 | متوسط\n" +
            "قیمت خودرو پژو ۲۰۶ | 6000 | سخت\n" +
            "آموزش زبان انگلیسی | 9000 | سخت\n" +
            "خرید لباس زنانه ارزان | 2200 | متوسط\n" +
            "نقد و بررسی مک‌بوک پرو | 500 | سخت\n" +
            "انواع پاور بانک | 1300 | آسان\n" +
            "راهنمای خرید تلویزیون | 750 | متوسط\n" +
            "فروش آنلاین قهوه | 400 | آسان\n" +
            "تفاوت SSD و HDD | 2000 | آسان";
    }

    function clearTextarea() {
        var textarea = document.querySelector('textarea#keywordsInput');
        if (textarea) textarea.value = '';
    }

    function initDefaultTab() {
        var panel = document.getElementById('tab-kw');
        if (panel) {
            panel.hidden = false;
            panel.classList.add('active');
        }

        document.querySelectorAll('.tab').forEach(function(tab, index) {
            if (index === 0) {
                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');
            } else {
                tab.setAttribute('aria-selected', 'false');
            }
        });
    }

    function enhanceCards() {
        var cards = document.querySelectorAll('.kpi-card, .chart-card, .cluster-card, .strategy-card, .roadmap-phase');
        cards.forEach(function(card, index) {
            card.style.animationDelay = Math.min(index * 35, 280) + 'ms';
            card.classList.add('is-ready');
        });
    }

    initDefaultTab();
    enhanceCards();

    window.switchTab    = switchTab;
    window.filterTable  = filterTable;
    window.sortTable    = sortTable;
    window.filterBy     = filterBy;
    window.loadSample   = loadSample;
    window.clearTextarea = clearTextarea;
})();
