<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$config = require __DIR__ . '/config.php';
require __DIR__ . '/database.php';

function respondJson($payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fallbackMenu(): array {
    return [
        'brandLogo' => '',
        'showcaseImage' => '',
        'heroDescription' => 'یک منوی گرم و مینیمال با ظاهر لوکس برای کافه پین.',
        'showcaseTitle' => 'فضای گرم، منوی پویا',
        'showcaseDescription' => 'ترکیبی از هویت بصری خاص، تصاویر تمیز و آیتم‌های کاملاً قابل مدیریت.',
        'footerBrandTitle' => 'میان',
        'footerInfo' => "ساعت کاری: شنبه تا پنجشنبه 8 صبح تا 11 شب\nجمعه 9 صبح تا 11 شب - قیمت‌ها به تومان",
        'footerLinks' => [
            ['label' => 'اینستاگرام', 'href' => '#'],
            ['label' => 'تلگرام', 'href' => '#'],
            ['label' => 'لوکیشن', 'href' => '#']
        ],
        'sections' => [
            [
                'id' => 'espresso',
                'icon' => '☕',
                'en' => 'Espresso Based',
                'fa' => 'اسپرسو بیس',
                'items' => [
                    ['id' => 'espresso', 'fa' => 'اسپرسو', 'en' => 'Espresso', 'price' => '65,000', 'desc' => 'شات خالص، تلخ و پرانرژی برای شروع روز.', 'tags' => ['hot'], 'featured' => true, 'emoji' => '☕', 'image' => ''],
                    ['id' => 'doppio', 'fa' => 'دوپیو', 'en' => 'Doppio', 'price' => '85,000', 'desc' => 'دو شات اسپرسو برای دوست‌داران قهوه پررنگ.', 'tags' => ['hot'], 'featured' => false, 'emoji' => '☕', 'image' => '']
                ]
            ],
            [
                'id' => 'cold',
                'icon' => '🧊',
                'en' => 'Cold Coffee',
                'fa' => 'نوشیدنی سرد',
                'items' => [
                    ['id' => 'coldbrew', 'fa' => 'کولد برو', 'en' => 'Cold Brew', 'price' => '145,000', 'desc' => 'دم‌آوری طولانی‌مدت با طعمی نرم و بدون تلخی اضافه.', 'tags' => ['cold'], 'featured' => true, 'emoji' => '🧊', 'image' => ''],
                    ['id' => 'icedlatte', 'fa' => 'آیس لاته', 'en' => 'Iced Latte', 'price' => '135,000', 'desc' => 'اسپرسو روی یخ با شیر سرد و بافتی ملایم.', 'tags' => ['cold'], 'featured' => false, 'emoji' => '🥤', 'image' => '']
                ]
            ],
            [
                'id' => 'dessert',
                'icon' => '🥐',
                'en' => 'Dessert & Pastry',
                'fa' => 'خوراکی و شیرینی',
                'items' => [
                    ['id' => 'croissant', 'fa' => 'کرواسان کره‌ای', 'en' => 'Butter Croissant', 'price' => '115,000', 'desc' => 'تازه از فر، ترد و کره‌ای.', 'tags' => ['new'], 'featured' => true, 'emoji' => '🥐', 'image' => ''],
                    ['id' => 'cheesecake', 'fa' => 'چیزکیک', 'en' => 'Basque Cheesecake', 'price' => '135,000', 'desc' => 'چیزکیک کرمی با بافت لطیف و پخت روزانه.', 'tags' => ['sweet'], 'featured' => false, 'emoji' => '🍰', 'image' => '']
                ]
            ]
        ]
    ];
}

try {
    $db = new Database($config);

    $settingsRows = $db->query("SELECT k, v FROM settings");
    $rows = $db->query("SELECT * FROM items ORDER BY category, position, id");

    if (!$rows) {
        respondJson([
            'success' => true,
            'isFallback' => true,
            'data' => fallbackMenu()
        ]);
    }

    $menu = [
        'brandLogo' => '',
        'showcaseImage' => '',
        'heroDescription' => '',
        'showcaseTitle' => '',
        'showcaseDescription' => '',
        'footerBrandTitle' => '',
        'footerInfo' => '',
        'footerLinks' => [],
        'sections' => []
    ];

    if ($settingsRows) {
        foreach ($settingsRows as $setting) {
            $value = $setting['v'];
            $decoded = json_decode($value, true);
            $menu[$setting['k']] = $decoded !== null ? $decoded : $value;
        }
    }

    foreach ($rows as $row) {
        $category = $row['category'] ?: 'uncategorized';
        if (!isset($menu['sections'][$category])) {
            $menu['sections'][$category] = [
                'id' => $category,
                'icon' => $row['section_icon'] ?? '☕',
                'en' => $row['section_en'] ?? '',
                'fa' => $row['section_fa'] ?? $category,
                'items' => []
            ];
        }

        $menu['sections'][$category]['items'][] = [
            'id' => (string) $row['slug'],
            'fa' => $row['name'],
            'en' => $row['en'] ?? '',
            'price' => $row['price'] ?? '',
            'desc' => $row['description'] ?? '',
            'tags' => json_decode($row['tags'] ?? '[]', true) ?: [],
            'featured' => (bool) $row['featured'],
            'emoji' => $row['emoji'] ?? '☕',
            'image' => $row['image'] ?? ''
        ];
    }

    $menu['sections'] = array_values($menu['sections']);
    respondJson([
        'success' => true,
        'isFallback' => false,
        'data' => $menu
    ]);
} catch (Throwable $e) {
    respondJson([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
