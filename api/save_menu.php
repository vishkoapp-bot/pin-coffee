<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$config = require __DIR__ . '/config.php';
require __DIR__ . '/database.php';

function respondJson($payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requireToken(array $config): void {
    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $token = $body['token'] ?? $_POST['token'] ?? $_GET['token'] ?? '';
    if ($token !== $config['admin_token']) {
        respondJson(['success' => false, 'error' => 'Unauthorized'], 403);
    }
}

function normalizeInput(array $input): array {
    if (isset($input['menuData']) && is_array($input['menuData'])) {
        return $input['menuData'];
    }
    return is_array($input) ? $input : [];
}

try {
    requireToken($config);

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $menu = normalizeInput($input);

    if (empty($menu)) {
        respondJson(['success' => false, 'error' => 'No menu data provided'], 422);
    }

    $db = new Database($config);
    $pdo = $db->pdo();
    $pdo->beginTransaction();

    $db->delete('items', '1=1');
    $db->delete('settings', 'k IN (?, ?, ?, ?, ?, ?, ?, ?)', ['brandLogo', 'showcaseImage', 'heroDescription', 'showcaseTitle', 'showcaseDescription', 'footerBrandTitle', 'footerInfo', 'footerLinks']);

    $settingsMap = [
        'brandLogo' => $menu['brandLogo'] ?? '',
        'showcaseImage' => $menu['showcaseImage'] ?? '',
        'heroDescription' => $menu['heroDescription'] ?? '',
        'showcaseTitle' => $menu['showcaseTitle'] ?? '',
        'showcaseDescription' => $menu['showcaseDescription'] ?? '',
        'footerBrandTitle' => $menu['footerBrandTitle'] ?? '',
        'footerInfo' => $menu['footerInfo'] ?? '',
        'footerLinks' => $menu['footerLinks'] ?? []
    ];

    foreach ($settingsMap as $key => $value) {
        $db->insert('settings', ['k' => $key, 'v' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    $position = 0;
    foreach (($menu['sections'] ?? []) as $section) {
        $category = $section['id'] ?? ('section-' . $position);
        foreach (($section['items'] ?? []) as $item) {
            $db->insert('items', [
                'category' => $category,
                'slug' => $item['id'] ?? ('item-' . $position),
                'section_icon' => $section['icon'] ?? '',
                'section_en' => $section['en'] ?? '',
                'section_fa' => $section['fa'] ?? '',
                'name' => $item['fa'] ?? '',
                'en' => $item['en'] ?? '',
                'price' => $item['price'] ?? '',
                'description' => $item['desc'] ?? '',
                'tags' => json_encode(array_values($item['tags'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'featured' => !empty($item['featured']) ? 1 : 0,
                'emoji' => $item['emoji'] ?? '',
                'image' => is_array($item['image'] ?? null) ? json_encode($item['image'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ($item['image'] ?? ''),
                'wide' => !empty($item['wide']) ? 1 : 0,
                'position' => $position++
            ]);
        }
    }

    $pdo->commit();
    respondJson(['success' => true, 'message' => 'Menu saved']);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respondJson(['success' => false, 'error' => $e->getMessage()], 500);
}
