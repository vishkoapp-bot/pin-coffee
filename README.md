# کافه پین — منو دیجیتال

اپلیکیشن وب برای نمایش و مدیریت منوی دیجیتال کافه پین. صفحه مشتری (RTL، فارسی) از API دیتابیس تغذیه می‌شود و پنل ادمین با session محافظت می‌شود.

## ساختار

```
api/      REST endpoint ها (get/save/update menu, upload, migrate, bump-cache)
assets/   CSS و JS فرانت‌اند (menu + admin)
uploads/  تصاویر آپلود شده (خارج از git)
seo-engine/ ابزار مستقل تحلیل کلمات کلیدی
.cpanel.yml  تنظیمات deploy به cPanel
.htaccess    rewrite rules و security headers
```

## ورودی‌های اصلی

- `index.php` — صفحه مشتری
- `login.php` / `logout.php` — session ادمین
- `admin.php` — پنل مدیریت (نیاز به لاگین)
- `admin.html` — legacy redirect به login

## نصب محلی

1. دیتابیس MySQL یا SQLite در `api/config.php` تنظیم شود.
2. `php api/migration.php` اجرا شود تا جداول ساخته و seed شوند.
3. توکن ادمین در `api/config.php` عوض شود.

## Deploy

cPanel از طریق `.cpanel.yml` کل ریپو را در `public_html/` کپی می‌کند. در پنل cPanel: Pull → Deploy HEAD Commit.