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
$adminToken = $config['admin_token'] ?? '';
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
      <div>
        <strong>مدیریت کافه پین</strong>
        <span>دسته‌بندی‌ها، آیتم‌ها، قیمت‌ها، تصاویر و اطلاعات عمومی منو را مدیریت کن.</span>
      </div>
      <div style="display:flex; gap:12px; align-items:center;">
        <a class="top-link" href="/index.html">مشاهده صفحه منو</a>
        <a class="top-link" href="/logout.php">خروج</a>
      </div>
    </header>

    <main class="admin-layout">
      <section class="admin-main">
        <div class="panel">
          <div class="panel-head">
            <strong>تنظیمات عمومی</strong>
          </div>
          <div class="panel-body">
            <div class="field">
              <label for="adminTokenInput">توکن API</label>
              <input id="adminTokenInput" class="admin-input" type="password" value="<?= htmlspecialchars($adminToken, ENT_QUOTES, 'UTF-8') ?>" readonly>
            </div>
            <div class="field">
              <label for="heroDescriptionInput">متن معرفی بالای صفحه</label>
              <textarea id="heroDescriptionInput" class="admin-textarea"></textarea>
            </div>
            <div class="field-row">
              <div class="field">
                <label for="showcaseTitleInput">عنوان باکس نمایشی</label>
                <input id="showcaseTitleInput" class="admin-input" type="text">
              </div>
              <div class="field">
                <label for="showcaseDescriptionInput">توضیح باکس نمایشی</label>
                <input id="showcaseDescriptionInput" class="admin-input" type="text">
              </div>
            </div>
            <div class="field-row">
              <div class="field">
                <label for="footerBrandTitleInput">عنوان فوتر</label>
                <input id="footerBrandTitleInput" class="admin-input" type="text">
              </div>
              <div class="field">
                <label for="footerInfoInput">اطلاعات فوتر</label>
                <textarea id="footerInfoInput" class="admin-textarea small-textarea"></textarea>
              </div>
            </div>
            <div class="field">
              <label for="logoInput">لوگوی کافه</label>
              <input id="logoInput" class="hidden-input" type="file" accept="image/*">
              <button class="upload-btn" type="button" id="logoUploadBtn">آپلود لوگو</button>
            </div>
            <div class="admin-preview" id="logoPreview"></div>
            <div class="field">
              <label for="showcaseImageInput">تصویر داخل کاپ نمایشی</label>
              <input id="showcaseImageInput" class="hidden-input" type="file" accept="image/*">
              <button class="upload-btn" type="button" id="showcaseImageUploadBtn">آپلود تصویر کاپ</button>
            </div>
            <div class="admin-preview" id="showcasePreview"></div>
            <div class="admin-actions">
              <button class="save-btn" type="button" id="saveGeneralBtn">ذخیره تنظیمات عمومی</button>
              <button class="reset-btn" type="button" id="removeLogoBtn">حذف لوگو</button>
              <button class="reset-btn" type="button" id="removeShowcaseImageBtn">حذف تصویر کاپ</button>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head between">
            <strong>دسته‌بندی‌ها</strong>
            <button class="save-btn" type="button" id="addSectionBtn">افزودن دسته‌بندی</button>
          </div>
          <div class="panel-body">
            <div class="field">
              <label for="sectionSelector">انتخاب دسته‌بندی</label>
              <select id="sectionSelector" class="admin-select"></select>
            </div>
            <div class="field-row">
              <div class="field">
                <label for="sectionFaInput">نام فارسی</label>
                <input id="sectionFaInput" class="admin-input" type="text">
              </div>
              <div class="field">
                <label for="sectionEnInput">نام انگلیسی</label>
                <input id="sectionEnInput" class="admin-input" type="text">
              </div>
            </div>
            <div class="field-row">
              <div class="field">
                <label for="sectionIconInput">آیکون یا ایموجی</label>
                <input id="sectionIconInput" class="admin-input" type="text">
              </div>
              <div class="field">
                <label for="sectionIdInput">شناسه انگلیسی</label>
                <input id="sectionIdInput" class="admin-input" type="text">
              </div>
            </div>
            <div class="admin-actions">
              <button class="save-btn" type="button" id="saveSectionBtn">ذخیره دسته‌بندی</button>
              <button class="reset-btn" type="button" id="deleteSectionBtn">حذف دسته‌بندی</button>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head between">
            <strong>آیتم‌ها</strong>
            <button class="save-btn" type="button" id="addItemBtn">افزودن آیتم</button>
          </div>
          <div class="panel-body">
            <div class="field">
              <label for="itemSelector">انتخاب آیتم</label>
              <select id="itemSelector" class="admin-select"></select>
            </div>
            <div class="field-row">
              <div class="field">
                <label for="itemSectionSelector">دسته‌بندی آیتم</label>
                <select id="itemSectionSelector" class="admin-select"></select>
              </div>
              <div class="field">
                <label for="itemIdInput">شناسه آیتم</label>
                <input id="itemIdInput" class="admin-input" type="text">
              </div>
            </div>
            <div class="field-row">
              <div class="field">
                <label for="nameFaInput">نام فارسی</label>
                <input id="nameFaInput" class="admin-input" type="text">
              </div>
              <div class="field">
                <label for="nameEnInput">نام انگلیسی</label>
                <input id="nameEnInput" class="admin-input" type="text">
              </div>
            </div>
            <div class="field-row">
              <div class="field">
                <label for="priceInput">قیمت</label>
                <input id="priceInput" class="admin-input" type="text">
              </div>
              <div class="field">
                <label for="emojiInput">ایموجی جایگزین</label>
                <input id="emojiInput" class="admin-input" type="text">
              </div>
            </div>
            <div class="field">
              <label for="descInput">توضیحات</label>
              <textarea id="descInput" class="admin-textarea"></textarea>
            </div>
            <div class="field">
              <label for="tagsInput">تگ‌ها</label>
              <input id="tagsInput" class="admin-input" type="text" placeholder="hot,cold,sweet,new,vegan">
            </div>
            <label class="checkbox-line">
              <input id="featuredInput" type="checkbox">
              آیتم ویژه باشد
            </label>
            <div class="field">
              <label for="itemImageInput">تصویر آیتم</label>
              <input id="itemImageInput" class="hidden-input" type="file" accept="image/*">
              <button class="upload-btn" type="button" id="itemImageUploadBtn">آپلود تصویر</button>
            </div>
            <div class="admin-preview" id="imagePreview"></div>
            <div class="admin-actions">
              <button class="save-btn" type="button" id="saveItemBtn">ذخیره آیتم</button>
              <button class="reset-btn" type="button" id="removeItemImageBtn">حذف تصویر</button>
              <button class="reset-btn" type="button" id="deleteItemBtn">حذف آیتم</button>
            </div>
          </div>
        </div>
      </section>

      <aside class="admin-side panel">
        <div class="panel-head">
          <strong>عملیات سریع</strong>
        </div>
        <div class="panel-body">
          <div class="admin-actions stack">
            <button class="save-btn" type="button" id="resetAllBtn">بازنشانی کل داده‌ها</button>
            <button class="save-btn" type="button" id="seedDatabaseBtn">Seed دیتابیس</button>
            <button class="save-btn" type="button" id="bumpCacheBtn">پاک‌سازی کش مرورگر</button>
            <a class="top-link full" href="/index.php">رفتن به صفحه مشتری</a>
          </div>
          <div class="status-note" id="statusNote" role="status" aria-live="polite"></div>
        </div>
      </aside>
    </main>
  </div>

  <script src="assets/js/menu-store.js"></script>
  <script src="assets/js/admin-app.js"></script>
</body>
</html>
