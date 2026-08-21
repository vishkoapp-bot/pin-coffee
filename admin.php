<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
$config = require __DIR__ . '/api/config.php';
if (empty($_SESSION['admin_authenticated'])) {
    header('Location: /login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>کافه پین | مدیریت منو</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/base.css">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-page">
  <div class="admin-shell">
    <header class="admin-topbar">
      <div class="topbar-brand">
        <div class="topbar-logo" aria-hidden="true">☕</div>
        <div>
          <strong>مدیریت کافه پین</strong>
          <span>منو، دسته‌بندی‌ها و تنظیمات عمومی را از اینجا مدیریت کن.</span>
        </div>
      </div>
      <div class="topbar-actions">
        <a class="top-link" href="/index.php" target="_blank" rel="noopener">مشاهده صفحه منو ↗</a>
        <a class="top-link danger" href="/logout.php">خروج</a>
      </div>
    </header>

    <nav class="admin-tabs" role="tablist" aria-label="بخش‌های مدیریت">
      <button class="tab-btn active" role="tab" data-tab="settings" aria-selected="true">⚙️ تنظیمات عمومی</button>
      <button class="tab-btn" role="tab" data-tab="sections" aria-selected="false">📂 دسته‌بندی‌ها <span class="tab-count" id="sectionsCount">0</span></button>
      <button class="tab-btn" role="tab" data-tab="items" aria-selected="false">☕ آیتم‌ها <span class="tab-count" id="itemsCount">0</span></button>
      <button class="tab-btn" role="tab" data-tab="operations" aria-selected="false">🛠 عملیات</button>
    </nav>

    <main class="admin-main">
      <!-- TAB: GENERAL SETTINGS -->
      <section class="tab-panel active" id="tab-settings" role="tabpanel">
        <div class="settings-grid">
          <div class="panel">
            <div class="panel-head">
              <strong>متن و عنوان‌ها</strong>
              <span class="panel-hint">متن‌هایی که در صفحه منو نمایش داده می‌شوند</span>
            </div>
            <div class="panel-body">
              <div class="field">
                <label for="heroDescriptionInput">متن معرفی بالای صفحه</label>
                <textarea id="heroDescriptionInput" class="admin-textarea" rows="3" placeholder="یک جمله کوتاه درباره فضای کافه..."></textarea>
              </div>
              <div class="field-row">
                <div class="field">
                  <label for="showcaseTitleInput">عنوان باکس نمایشی</label>
                  <input id="showcaseTitleInput" class="admin-input" type="text" placeholder="مثلا: فضای گرم، منوی پویا">
                </div>
                <div class="field">
                  <label for="showcaseDescriptionInput">توضیح باکس نمایشی</label>
                  <input id="showcaseDescriptionInput" class="admin-input" type="text" placeholder="یک خط توضیح کوتاه">
                </div>
              </div>
              <div class="field-row">
                <div class="field">
                  <label for="footerBrandTitleInput">عنوان فوتر</label>
                  <input id="footerBrandTitleInput" class="admin-input" type="text" placeholder="نام برند">
                </div>
                <div class="field">
                  <label for="footerInfoInput">اطلاعات فوتر (ساعات کاری، آدرس...)</label>
                  <textarea id="footerInfoInput" class="admin-textarea small-textarea" rows="3"></textarea>
                </div>
              </div>
              <div class="admin-actions">
                <button class="save-btn" type="button" id="saveGeneralBtn">ذخیره تنظیمات</button>
              </div>
            </div>
          </div>

          <div class="panel">
            <div class="panel-head">
              <strong>تصاویر</strong>
              <span class="panel-hint">لوگو و تصویر کاپ نمایشی</span>
            </div>
            <div class="panel-body">
              <div class="image-edit">
                <div class="image-edit-preview" id="logoPreview"></div>
                <div class="image-edit-controls">
                  <strong>لوگوی کافه</strong>
                  <span class="muted">PNG/SVG پیشنهاد می‌شود. حداکثر 5MB.</span>
                  <div class="admin-actions">
                    <button class="upload-btn" type="button" id="logoUploadBtn">📤 آپلود لوگو</button>
                    <button class="reset-btn" type="button" id="removeLogoBtn">🗑 حذف</button>
                  </div>
                </div>
                <input id="logoInput" class="hidden-input" type="file" accept="image/*">
              </div>

              <div class="image-edit">
                <div class="image-edit-preview" id="showcasePreview"></div>
                <div class="image-edit-controls">
                  <strong>تصویر داخل کاپ نمایشی</strong>
                  <span class="muted">تصویر اصلی که در فوتر نمایش داده می‌شود.</span>
                  <div class="admin-actions">
                    <button class="upload-btn" type="button" id="showcaseImageUploadBtn">📤 آپلود تصویر</button>
                    <button class="reset-btn" type="button" id="removeShowcaseImageBtn">🗑 حذف</button>
                  </div>
                </div>
                <input id="showcaseImageInput" class="hidden-input" type="file" accept="image/*">
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- TAB: SECTIONS -->
      <section class="tab-panel" id="tab-sections" role="tabpanel">
        <div class="panel">
          <div class="panel-head between">
            <div>
              <strong>دسته‌بندی‌ها</strong>
              <span class="panel-hint">برای مرتب‌سازی، کارت‌ها را بکشید و جابجا کنید</span>
            </div>
            <button class="save-btn" type="button" id="addSectionBtn">➕ افزودن دسته‌بندی</button>
          </div>
          <div class="panel-body">
            <div class="card-list" id="sectionsList" aria-label="لیست دسته‌بندی‌ها"></div>
            <div class="empty-state" id="sectionsEmpty" hidden>
              <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
                <path d="M16 24h32v18a8 8 0 0 1-8 8H24a8 8 0 0 1-8-8V24Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M48 28h4a4 4 0 0 1 0 8h-4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M22 8c2 2-2 5 0 7s-2 5 0 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M32 6c2 2-2 5 0 7s-2 5 0 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M42 8c2 2-2 5 0 7s-2 5 0 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              </svg>
              <h3>هنوز دسته‌بندی نداری</h3>
              <p>با کلیک روی «افزودن دسته‌بندی» اولین دسته را بساز.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- TAB: ITEMS -->
      <section class="tab-panel" id="tab-items" role="tabpanel">
        <div class="panel">
          <div class="panel-head">
            <div class="items-head">
              <div>
                <strong>آیتم‌های منو</strong>
                <span class="panel-hint">کارت‌ها برای ویرایش. روی «ویرایش» کلیک کن.</span>
              </div>
              <div class="items-controls">
                <select id="itemsFilter" class="admin-select compact">
                  <option value="">همه دسته‌ها</option>
                </select>
                <input id="itemsSearch" class="admin-input compact" type="search" placeholder="🔍 جستجو...">
                <button class="save-btn" type="button" id="addItemBtn">➕ افزودن آیتم</button>
              </div>
            </div>
          </div>
          <div class="panel-body">
            <div class="card-list items-grid" id="itemsList" aria-label="لیست آیتم‌ها"></div>
            <div class="empty-state" id="itemsEmpty" hidden>
              <svg viewBox="0 0 64 64" fill="none" aria-hidden="true">
                <path d="M20 28h24v14a8 8 0 0 1-8 8H28a8 8 0 0 1-8-8V28Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M44 32h4a4 4 0 0 1 0 8h-4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                <path d="M28 20a4 4 0 0 1 8 0v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="22" cy="16" r="1.5" fill="currentColor"/>
                <circle cx="32" cy="12" r="1.5" fill="currentColor"/>
                <circle cx="42" cy="16" r="1.5" fill="currentColor"/>
              </svg>
              <h3>هنوز آیتمی نداری</h3>
              <p>با کلیک روی «افزودن آیتم» اولین آیتم را اضافه کن.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- TAB: OPERATIONS -->
      <section class="tab-panel" id="tab-operations" role="tabpanel">
        <div class="operations-grid">
          <div class="panel op-card">
            <div class="op-icon" aria-hidden="true">🔄</div>
            <strong>بازنشانی کل داده‌ها</strong>
            <p>تمام منو با داده‌های پیش‌فرض جایگزین می‌شود. تغییرات اخیر از دست می‌روند.</p>
            <button class="save-btn danger" type="button" id="resetAllBtn">بازنشانی</button>
          </div>
          <div class="panel op-card">
            <div class="op-icon" aria-hidden="true">🌱</div>
            <strong>Seed دیتابیس</strong>
            <p>اگر دیتابیس خالی است، جداول ساخته و با داده نمونه پر می‌شوند.</p>
            <button class="save-btn" type="button" id="seedDatabaseBtn">اجرای Seed</button>
          </div>
          <div class="panel op-card">
            <div class="op-icon" aria-hidden="true">⚡</div>
            <strong>پاک‌سازی کش مرورگر</strong>
            <p>نسخه فایل‌های CSS/JS را افزایش بده تا کاربران کش قدیمی را نگه ندارند.</p>
            <button class="save-btn" type="button" id="bumpCacheBtn">پاک‌سازی کش</button>
          </div>
        </div>
      </section>
    </main>
  </div>

  <!-- Edit Drawer (modal for editing an item) -->
  <div class="drawer" id="editDrawer" aria-hidden="true" role="dialog" aria-labelledby="drawerTitle">
    <div class="drawer-backdrop" data-drawer-close></div>
    <div class="drawer-panel">
      <header class="drawer-head">
        <div>
          <strong id="drawerTitle">ویرایش آیتم</strong>
          <span class="panel-hint" id="drawerSubtitle"></span>
        </div>
        <button class="drawer-close" type="button" data-drawer-close aria-label="بستن">×</button>
      </header>
      <div class="drawer-body" id="drawerBody">
        <!-- form is injected dynamically -->
      </div>
      <footer class="drawer-foot">
        <button class="reset-btn danger" type="button" id="drawerDeleteBtn">🗑 حذف</button>
        <div class="drawer-foot-right">
          <button class="reset-btn" type="button" data-drawer-close>انصراف</button>
          <button class="save-btn" type="button" id="drawerSaveBtn">💾 ذخیره</button>
        </div>
      </footer>
    </div>
  </div>

  <!-- Confirm dialog -->
  <div class="confirm-dialog" id="confirmDialog" aria-hidden="true" role="alertdialog">
    <div class="confirm-backdrop" data-confirm-close></div>
    <div class="confirm-panel">
      <strong id="confirmTitle">تأیید عملیات</strong>
      <p id="confirmMessage">آیا مطمئن هستی؟</p>
      <div class="confirm-actions">
        <button class="reset-btn" type="button" data-confirm-close>انصراف</button>
        <button class="save-btn danger" type="button" id="confirmOk">تأیید</button>
      </div>
    </div>
  </div>

  <!-- Toast container -->
  <div class="toast-container" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

  <script src="assets/js/menu-store.js"></script>
  <script src="assets/js/admin-app.js"></script>
</body>
</html>