# کافه میان — منو دیجیتال (PHP + API)

## نمای کلی

یک منوی دیجیتال مدرن برای کافه با پنل مدیریت و API برای ذخیره داده‌های دیتابیس.

**ویژگی‌ها:**
- ✅ منوی دیتابیسی (SQLite/MySQL)
- ✅ پنل مدیریت برای ویرایش قیمت، تصاویر، توضیحات
- ✅ آپلود تصاویر
- ✅ Fallback به localStorage اگر API در دسترس نباشد
- ✅ Export/Import داده‌ها
- ✅ انیمیشن‌های زیاد و رنگ‌بندی جذاب

---

## ساختار پروژه

```
miyan/
├── index-api.html          # فرانت‌اند (استفاده کنید در جای cafe-miyan-menu.html)
├── api/
│   ├── config.php          # تنظیمات دیتابیس و توکن ادمین
│   ├── database.php        # کلاس PDO برای اتصال
│   ├── migrate.php         # اجرای مایگریشن‌ها
│   ├── get_menu.php        # API: خواندن منو
│   ├── save_menu.php       # API: ذخیره کل منو
│   ├── update_item.php     # API: بروزرسانی آیتم منفرد
│   ├── upload_image.php    # API: آپلود تصویر
│   └── migrations/
│       └── 001_create_tables.sql  # ساخت جداول
├── uploads/                # پوشه تصاویر آپلود شده
└── .gitignore             # (پیشنهاد)
```

---

## راه‌اندازی

### 1️⃣ تنظیم دیتابیس

#### گزینه الف: SQLite (سادگی بیشتر)

```bash
# هیچ تنظیمی نیاز نیست! SQLite خودکار ایجاد می‌شود
# فقط `api/config.php` را بررسی کنید:
# 'driver' => 'sqlite' (پیش‌فرض است)
```

#### گزینه ب: MySQL

`api/config.php` را ویرایش کنید:

```php
'driver' => 'mysql',
'mysql' => [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'miyan_db',
    'username' => 'root',
    'password' => ''
],
```

سپس دیتابیس را ایجاد کنید:

```sql
CREATE DATABASE miyan_db CHARACTER SET utf8mb4;
```

### 2️⃣ اجرای مایگریشن

ترمینال را در فولدر `api/` باز کنید:

```bash
php migrate.php
```

Output:

```
Applying migration: 001_create_tables.sql
Migrations applied.
```

### 3️⃣ تنظیم توکن ادمین

`api/config.php` را باز کنید و توکن را تغییر دهید:

```php
'admin_token' => 'your_secret_token_here',
```

**توصیه:** یک رشته‌ی پیچیده استفاده کنید (مثلاً: `abc123xyz789!@#`)

### 4️⃣ دسترسی به وب‌سایت

اگر از PHP's built-in server استفاده می‌کنید:

```bash
cd d:\Downloads\miyan
php -S localhost:8000
```

سپس به این آدرس بروید:

```
http://localhost:8000/index-api.html
```

---

## استفاده از پنل مدیریت

1. **دکمه "⚙️ مدیریت"** را در پایین‌چپ کلیک کنید
2. **توکن ادمین** را وارد کنید (همان‌طور که در `config.php` تنظیم کردید)
3. **آیتم‌ها را ویرایش کنید:**
   - نام، قیمت، توضیح
   - URL تصویر
   - برچسب‌ها (محبوب، جدید، وغیره)

4. **تصاویر آپلود کنید:** URL تصویر را درج کنید (لینک مستقیم)

5. **ذخیره شود** خودکار!

---

## API Endpoints

### 1. دریافت منو
```
GET /api/get_menu.php
```

Response:
```json
{
  "espresso": [
    {
      "id": 1,
      "name": "اسپرسو",
      "en": "Espresso",
      "price": "۶۵,۰۰۰",
      "desc": "شات خالص",
      "tags": ["hot"],
      "featured": true,
      "image": ""
    }
  ]
}
```

### 2. ذخیره کل منو
```
POST /api/save_menu.php
Content-Type: application/json

{
  "token": "your_secret_token",
  "menuData": { ... }
}
```

### 3. بروزرسانی آیتم منفرد
```
POST /api/update_item.php
Content-Type: application/json

{
  "token": "your_secret_token",
  "id": 1,
  "field": "price",
  "value": "۷۵,۰۰۰"
}
```

### 4. آپلود تصویر
```
POST /api/upload_image.php

FormData:
  - image: <file>
  - token: your_secret_token
```

Response:
```json
{
  "success": true,
  "url": "/uploads/abc123def.jpg",
  "filename": "abc123def.jpg"
}
```

---

## نمونه cURL

### دریافت منو:
```bash
curl http://localhost:8000/api/get_menu.php
```

### ذخیره منو:
```bash
curl -X POST http://localhost:8000/api/save_menu.php \
  -H "Content-Type: application/json" \
  -d '{
    "token": "your_secret_token",
    "menuData": {"espresso": [...]}
  }'
```

### آپلود تصویر:
```bash
curl -X POST http://localhost:8000/api/upload_image.php \
  -F "image=@/path/to/image.jpg" \
  -F "token=your_secret_token"
```

---

## نکات مهم

⚠️ **Security:**
- توکن را قبل از deploy تغییر دهید
- HTTPS استفاده کنید در production
- Permissions فایل‌ها را بررسی کنید

📁 **پوشه Uploads:**
- اطمینان حاصل کنید `uploads/` قابل نوشتن است
- مسیر: `d:\Downloads\miyan\uploads\`

🔄 **Fallback:**
- اگر API دسترس‌پذیر نباشد، داده‌ها در localStorage ذخیره می‌شوند
- می‌توانید export/import کنید

---

## Troubleshooting

### خطا: "API error"
- `api/get_menu.php` در دسترس نیست
- تصویر دوباره بارگذاری کنید (F5)

### خطا: "Unauthorized"
- توکن غلط است
- `api/config.php` را بررسی کنید

### تصاویر آپلود نمی‌شوند
- `uploads/` قابل نوشتن است؟
- `chmod 755 uploads/` (در Linux/Mac)

---

## اگر از فایل فعلی `cafe-miyan-menu.html` استفاده می‌کنید

می‌توانید از `index-api.html` کپی کنید یا هر دو را نگاه دارید:

```bash
# گزینه 1: جایگزینی
mv cafe-miyan-menu.html cafe-miyan-menu-old.html
cp index-api.html cafe-miyan-menu.html

# گزینه 2: هردو را نگاه دارید
# cafe-miyan-menu.html = فعلی (بدون API)
# index-api.html = جدید (با API)
```

---

## فایل‌های اضافی پیشنهادی

### .gitignore
```
uploads/*
!uploads/.gitkeep
api/database.sqlite
.DS_Store
*.swp
```

---

## پیام‌های خطا و حل

| مسئله | راه‌حل |
|-------|--------|
| "CORS error" | اگر `index-api.html` از دومین دیگری باشد، CORS header‌های `api/*.php` را بررسی کنید |
| "توکن لازم است" | توکن را در پنل مدیریت وارد کنید |
| داده‌ها ذخیره نمی‌شود | `uploads/` یا دیتابیس قابل نوشتن نیست |

---

**نسخه:** 1.0  
**تاریخ:** May 2026  
**توسط:** GitHub Copilot
