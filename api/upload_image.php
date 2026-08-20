<?php
// upload_image.php - Handle image uploads for menu items
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$config = require __DIR__ . '/config.php';
require __DIR__ . '/auth.php';

$token = $_POST['token'] ?? $_GET['token'] ?? null;
if (!isAdminLoggedIn() && $token !== $config['admin_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    if (!isset($_FILES['image'])) {
        throw new Exception('No image uploaded');
    }
    
    $file = $_FILES['image'];
    $uploadsDir = $config['uploads_dir'];
    
    if (!is_dir($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
    }
    
    // Validate file
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed)) {
        throw new Exception('Invalid image type');
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        throw new Exception('Image too large');
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = md5(uniqid()) . '.' . $ext;
    $filepath = $uploadsDir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save image');
    }
    
    // Return relative URL
    $imageUrl = '/uploads/' . $filename;
    
    echo json_encode([
        'success' => true,
        'url' => $imageUrl,
        'filename' => $filename
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
