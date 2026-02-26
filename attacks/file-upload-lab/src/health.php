<?php
/**
 * Health Check Endpoint - OBLIGATOIRE pour DIABLE
 * Retourne le statut du service en JSON
 */

header('Content-Type: application/json');

$status = 'healthy';
$code = 200;
$checks = [];

// Vérifier que le dossier uploads est accessible
$upload_dir = __DIR__ . '/uploads';
if (!is_dir($upload_dir)) {
    $status = 'unhealthy';
    $checks['uploads'] = [
        'status' => 'error',
        'message' => 'Dossier uploads manquant'
    ];
} elseif (!is_writable($upload_dir)) {
    $status = 'degraded';
    $checks['uploads'] = [
        'status' => 'warning',
        'message' => 'Dossier uploads non accessible en écriture'
    ];
} else {
    $checks['uploads'] = [
        'status' => 'ok',
        'message' => 'Dossier uploads accessible'
    ];
}

// Vérifier que PHP fonctionne
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    $status = 'degraded';
    $checks['php'] = [
        'status' => 'warning',
        'message' => 'Version PHP ancienne: ' . PHP_VERSION
    ];
} else {
    $checks['php'] = [
        'status' => 'ok',
        'message' => 'PHP ' . PHP_VERSION
    ];
}

// Vérifier les permissions des fichiers
$test_file = $upload_dir . '/health_test.tmp';
if (@file_put_contents($test_file, 'test')) {
    unlink($test_file);
    $checks['write'] = [
        'status' => 'ok',
        'message' => 'Écriture possible dans uploads'
    ];
} else {
    $status = 'unhealthy';
    $checks['write'] = [
        'status' => 'error',
        'message' => 'Impossible d\'écrire dans uploads'
    ];
}

// Construire la réponse
$response = [
    'status' => $status,
    'service' => 'file-upload-lab',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => $checks
];

// Définir le code HTTP
if ($status === 'unhealthy') {
    http_response_code(503);
} elseif ($status === 'degraded') {
    http_response_code(200); // Toujours 200 mais avec warning
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
