<?php
header('Content-Type: application/json');

$status = 'healthy';
$checks = [];

// Vérifier que libxml fonctionne
if (!extension_loaded('simplexml')) {
    $status = 'unhealthy';
    $checks['libxml'] = ['status' => 'error', 'message' => 'Extension XML manquante'];
} else {
    $checks['libxml'] = ['status' => 'ok', 'message' => 'Extension XML chargée'];
}

// Vérifier les fichiers de test
$test_files = ['/tmp/flag.txt', '/tmp/passwd', '/tmp/config.txt'];
foreach ($test_files as $file) {
    if (!file_exists($file)) {
        $checks['files'] = ['status' => 'warning', 'message' => "$file manquant"];
    }
}

echo json_encode([
    'status' => $status,
    'service' => 'xxe-lab',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => $checks
], JSON_PRETTY_PRINT);
