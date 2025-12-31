<?php
// Allow overriding DB_PATH via environment variable (for Docker)
$dbPath = getenv('DB_PATH');
if (!$dbPath) {
    $dbPath = __DIR__ . '/../database.sqlite';
}
define('DB_PATH', $dbPath);
