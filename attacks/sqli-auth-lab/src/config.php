<?php
$dbPath = getenv('DB_PATH') ?: __DIR__ . '/data/lab.db';
$debugMode = filter_var(getenv('DEBUG_MODE') ?: false, FILTER_VALIDATE_BOOLEAN);
$labName = getenv('LAB_NAME') ?: 'sqli-auth-lab';

function getDb(): PDO {
    global $dbPath;
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
