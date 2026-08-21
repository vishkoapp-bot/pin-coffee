<?php
header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';
require __DIR__ . '/database.php';

$seed = [
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

function out(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function columnExists(Database $db, string $table, string $column): bool {
    $row = $db->queryOne(
        "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
        [$table, $column]
    );
    return (int)($row['c'] ?? 0) > 0;
}

function ensureColumn(Database $db, string $table, string $column, string $definition): void {
    if (!columnExists($db, $table, $column)) {
        $db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}

function ensureUtf8mb4(Database $db, string $table): void {
    $db->exec("ALTER TABLE {$table} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function indexExists(Database $db, string $table, string $indexName): bool {
    $row = $db->queryOne(
        "SELECT COUNT(*) AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
        [$table, $indexName]
    );
    return (int)($row['c'] ?? 0) > 0;
}

try {
    $db = new Database($config);
    $pdo = $db->pdo();
    $pdo->beginTransaction();

    $db->exec("
        CREATE TABLE IF NOT EXISTS settings (
          k VARCHAR(255) PRIMARY KEY,
          v TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS items (
          id INT NOT NULL AUTO_INCREMENT,
          category VARCHAR(255) NOT NULL,
          slug VARCHAR(255) NOT NULL,
          section_icon VARCHAR(32) DEFAULT '',
          section_en VARCHAR(255) DEFAULT '',
          section_fa VARCHAR(255) DEFAULT '',
          name VARCHAR(255) NOT NULL,
          en VARCHAR(255),
          price VARCHAR(255),
          description TEXT,
          tags TEXT,
          emoji VARCHAR(32) DEFAULT '',
          featured TINYINT(1) DEFAULT 0,
          image TEXT,
          wide TINYINT(1) DEFAULT 0,
          position INT DEFAULT 0,
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    ensureColumn($db, 'items', 'slug', "VARCHAR(255) NOT NULL DEFAULT '' AFTER category");
    ensureColumn($db, 'items', 'section_icon', "VARCHAR(32) DEFAULT '' AFTER slug");
    ensureColumn($db, 'items', 'section_en', "VARCHAR(255) DEFAULT '' AFTER section_icon");
    ensureColumn($db, 'items', 'section_fa', "VARCHAR(255) DEFAULT '' AFTER section_en");
    ensureColumn($db, 'items', 'emoji', "VARCHAR(32) DEFAULT '' AFTER tags");

    if (!indexExists($db, 'items', 'idx_items_category')) {
        $db->exec("CREATE INDEX idx_items_category ON items(category)");
    }
    if (!indexExists($db, 'items', 'idx_items_slug')) {
        $db->exec("CREATE UNIQUE INDEX idx_items_slug ON items(slug)");
    }

    ensureUtf8mb4($db, 'settings');
    ensureUtf8mb4($db, 'items');

    $force = isset($_GET['force']) && $_GET['force'] === '1';
    $hasItems = (int)($db->queryOne("SELECT COUNT(*) AS c FROM items")['c'] ?? 0) > 0;
    $hasSettings = (int)($db->queryOne("SELECT COUNT(*) AS c FROM settings")['c'] ?? 0) > 0;

    if (!$force && ($hasItems || $hasSettings)) {
        $pdo->commit();
        out([
            'success' => true,
            'message' => 'Database already initialized',
            'hint' => 'Run with ?force=1 if you want to overwrite the existing data.'
        ]);
    }

    $db->delete('items', '1=1');
    $db->delete('settings', '1=1');

    foreach (['brandLogo', 'showcaseImage', 'heroDescription', 'showcaseTitle', 'showcaseDescription', 'footerBrandTitle', 'footerInfo', 'footerLinks'] as $key) {
        $db->insert('settings', [
            'k' => $key,
            'v' => json_encode($seed[$key] ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);
    }

    $position = 0;
    foreach ($seed['sections'] as $section) {
        foreach ($section['items'] as $item) {
            $db->insert('items', [
                'category' => $section['id'],
                'slug' => $item['id'],
                'section_icon' => $section['icon'],
                'section_en' => $section['en'],
                'section_fa' => $section['fa'],
                'name' => $item['fa'],
                'en' => $item['en'],
                'price' => $item['price'],
                'description' => $item['desc'],
                'tags' => json_encode($item['tags'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'emoji' => $item['emoji'],
                'featured' => $item['featured'] ? 1 : 0,
                'image' => $item['image'],
                'wide' => 0,
                'position' => $position++
            ]);
        }
    }

    $pdo->commit();
    out([
        'success' => true,
        'message' => 'Migration completed',
        'tables' => ['settings', 'items'],
        'seeded_sections' => count($seed['sections']),
        'seeded_items' => $position
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out(['success' => false, 'error' => $e->getMessage()], 500);
}
