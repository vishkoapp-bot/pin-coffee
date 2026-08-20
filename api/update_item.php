<?php
// update_item.php - Update a single menu item
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
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $id = $input['id'] ?? null;
    $field = $input['field'] ?? null;
    $value = $input['value'] ?? null;
    
    if (!$id || !$field) {
        throw new Exception('Missing id or field');
    }
    
    // Map field names
    $fieldMap = [
        'name' => 'name',
        'en' => 'en',
        'price' => 'price',
        'desc' => 'description',
        'tags' => 'tags',
        'featured' => 'featured',
        'image' => 'image',
        'wide' => 'wide',
        'category' => 'category',
        'slug' => 'slug',
        'section_icon' => 'section_icon',
        'section_en' => 'section_en',
        'section_fa' => 'section_fa'
    ];
    
    if (!isset($fieldMap[$field])) {
        throw new Exception('Invalid field');
    }
    
    $dbField = $fieldMap[$field];
    
    // Handle special types
    if ($field === 'tags') {
        $value = json_encode($value);
    } elseif ($field === 'featured' || $field === 'wide') {
        $value = $value ? 1 : 0;
    }
    
    $db->update('items', [$dbField => $value], 'id = ?', [$id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
