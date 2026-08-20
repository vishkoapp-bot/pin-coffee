<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$config = require __DIR__ . '/config.php';
require __DIR__ . '/database.php';
require __DIR__ . '/auth.php';

$token = $_POST['token'] ?? $_GET['token'] ?? null;
if (!isAdminLoggedIn() && $token !== $config['admin_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = new Database($config);
    $value = (string) time();

    $existing = $db->queryOne('SELECT k FROM settings WHERE k = ?', ['assetCacheBust']);
    if ($existing) {
        $db->update('settings', ['v' => $value], 'k = ?', ['assetCacheBust']);
    } else {
        $db->insert('settings', ['k' => 'assetCacheBust', 'v' => $value]);
    }

    echo json_encode(['success' => true, 'cacheBust' => $value]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
