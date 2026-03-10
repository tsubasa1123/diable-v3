<?php
$dbPath = getenv('DB_PATH') ?: __DIR__ . '/data/lab.db';
$debugMode = filter_var(getenv('DEBUG_MODE') ?: false, FILTER_VALIDATE_BOOLEAN);
$labName = getenv('LAB_NAME') ?: 'sqli-auth-lab';
$finalFlag = getenv('FINAL_FLAG') ?: 'DIABLE{sqli_master_of_three_levels}';

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

function getProgress(): array {
    $default = ['easy' => false, 'medium' => false, 'hard' => false];
    if (empty($_COOKIE['lab_progress'])) {
        return $default;
    }

    $decoded = json_decode($_COOKIE['lab_progress'], true);
    if (!is_array($decoded)) {
        return $default;
    }

    return array_merge($default, array_intersect_key($decoded, $default));
}

function saveProgress(array $progress): void {
    setcookie('lab_progress', json_encode($progress), time() + 86400 * 30, '/');
    $_COOKIE['lab_progress'] = json_encode($progress);
}

function completeLevel(string $level): void {
    $progress = getProgress();
    if (array_key_exists($level, $progress)) {
        $progress[$level] = true;
        saveProgress($progress);
    }
}

function allLevelsCompleted(): bool {
    $progress = getProgress();
    return !in_array(false, $progress, true);
}
