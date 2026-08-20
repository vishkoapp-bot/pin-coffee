<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_authenticated']);
}

function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function adminTokenFromConfig(array $config): string {
    return $config['admin_token'] ?? '';
}
