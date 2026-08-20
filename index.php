<?php
$config = require __DIR__ . '/api/config.php';

function asset_version(string $path, int $bump = 0): string {
    $fullPath = __DIR__ . '/' . ltrim($path, '/');
    $mtime = is_file($fullPath) ? filemtime($fullPath) : time();
    return (string) ($mtime + $bump);
}

$cacheBump = 0;
try {
    require __DIR__ . '/api/database.php';
    $db = new Database($config);
    $row = $db->queryOne("SELECT v FROM settings WHERE k = ?", ['assetCacheBust']);
    $cacheValue = $row['v'] ?? 0;
    $decodedCache = json_decode($cacheValue, true);
    $cacheBump = (int) ($decodedCache !== null ? $decodedCache : $cacheValue);
} catch (Throwable $e) {
  $cacheBump = 0;
}
$cssBaseVersion = asset_version('assets/css/base.css', $cacheBump);
$menuCssVersion = asset_version('assets/css/menu.css', $cacheBump);
$menuStoreVersion = asset_version('assets/js/menu-store.js', $cacheBump);
$menuAppVersion = asset_version('assets/js/menu-app.js', $cacheBump);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
  <meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Expires" content="0">
  <title>کافه پین | منو</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/base.css?v=<?= htmlspecialchars($cssBaseVersion, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="assets/css/menu.css?v=<?= htmlspecialchars($menuCssVersion, ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="menu-page">
  <div class="page-shell">
    <section class="hero">
      <div class="hero-panel">
        <div class="hero-grid">
          <div class="hero-copy">
            <div class="hero-badge">منوی دیجیتال کافه پین</div>
            <div class="brand-row">
              <div class="logo-banner" id="brandLogoFrame">
                <div class="logo-orbit logo-orbit-one"></div>
                <div class="logo-orbit logo-orbit-two"></div>
                <div class="logo-orbit logo-orbit-three"></div>
                <span class="logo-dot dot-one"></span>
                <span class="logo-dot dot-two"></span>
                <span class="logo-dot dot-three"></span>
                <img id="brandLogoImage" alt="لوگوی کافه پین">
                <div class="logo-placeholder">جای لوگوی شما</div>
              </div>
            </div>


          <div class="hero-visual">
            <div class="footer-card info-card">
              <div class="footer-brand">
                <strong id="footerBrandTitle"></strong>
                <span id="footerInfo"></span>
              </div>
              <div class="footer-links" id="footerLinks"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <nav class="menu-nav">
      <div class="nav-inner" id="navButtons"></div>
    </nav>

    <main class="layout">
      <section class="menu-column" id="menuSections"></section>
    </main>

    <footer class="footer">
      <div class="footer-card footer-visual">
        <div class="visual-orbit"></div>
        <div class="featured-showcase">
          <div class="showcase-label">امضای پین</div>
          <div class="showcase-media">
            <img id="showcaseHeroImage" class="showcase-image" alt="تصویر نمایشی">
            <div class="showcase-media-fallback" aria-hidden="true">☕</div>
          </div>
          <div class="showcase-copy">
            <strong id="showcaseTitle"></strong>
            <span id="showcaseDescription"></span>
          </div>
        </div>
      </div>
    </footer>

    <div class="item-modal" id="itemModal" aria-hidden="true">
      <div class="item-modal-backdrop" data-modal-close></div>
      <div class="item-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="itemModalTitle">
        <button class="item-modal-close" type="button" data-modal-close aria-label="بستن پاپ‌آپ">×</button>
        <div class="item-modal-visual" id="itemModalVisual">
          <img id="itemModalImage" alt="">
          <div class="item-modal-fallback" id="itemModalFallback" aria-hidden="true">☕</div>
        </div>
        <div class="item-modal-body">
          <div class="item-modal-meta">
            <span class="item-modal-badge" id="itemModalBadge"></span>
            <span class="item-modal-price" id="itemModalPrice"></span>
          </div>
          <h3 id="itemModalTitle"></h3>
          <p class="item-modal-en" id="itemModalSubtitle"></p>
          <p class="item-modal-desc" id="itemModalDescription"></p>
          <div class="item-modal-tags" id="itemModalTags"></div>
          <p class="item-modal-hint">برای بستن، بیرون پاپ‌آپ را لمس کنید یا Esc را بزنید.</p>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/js/menu-store.js?v=<?= htmlspecialchars($menuStoreVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
  <script src="assets/js/menu-app.js?v=<?= htmlspecialchars($menuAppVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
