<?php
/**
 * @var SeoEngine\Application $app
 */
?>
<section class="panel input-section">
    <div class="input-section__inner">
        <h2 class="input-section__title">🧠 ورود داده برای تحلیل</h2>
        <p class="input-section__desc">فرمت پیشنهادی: <strong>کلمه کلیدی | حجم جست‌وجو | سختی</strong> (در هر خط یک رکورد)</p>
        <form method="post" id="mainForm" class="input-form" aria-label="فرم ارسال داده تحلیل">
            <label for="keywordsInput">لیست کلمات کلیدی</label>
            <textarea
                id="keywordsInput"
                name="keywords"
                placeholder="خرید صندل زنانه | 1000 | سخت
کفش ورزشی مردانه | 5000 | متوسط
آموزش سئو سایت | 3000 | آسان"
            ><?= $app->e($app->getRawInput()) ?></textarea>
            <div class="actions">
                <button type="submit" class="btn btn--primary">🚀 تحلیل کلمات کلیدی</button>
                <button type="button" class="btn btn--secondary" onclick="clearTextarea()">🗑️ پاک‌سازی ورودی</button>
                <button type="button" class="btn btn--ghost" onclick="loadSample()">📝 نمونه داده</button>
            </div>
        </form>
    </div>
</section>
