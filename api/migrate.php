<?php
// Simple migration runner for sqlite or mysql based on config.php
$config = require __DIR__ . '/config.php';
require __DIR__ . '/database.php';

try {
    $db = new Database($config);

    $migrationsDir = __DIR__ . '/migrations';
    $files = glob($migrationsDir . '/*.sql');
    sort($files);
    foreach ($files as $f) {
        echo "Applying migration: " . basename($f) . PHP_EOL;
        $sql = file_get_contents($f);
        $db->exec($sql);
    }
    echo "Migrations applied." . PHP_EOL;
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . PHP_EOL;
}
