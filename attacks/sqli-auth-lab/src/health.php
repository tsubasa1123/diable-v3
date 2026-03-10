<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

$response = [
    'status' => 'healthy',
    'service' => $labName,
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => [
        'database' => [
            'status' => 'ok',
            'path' => $dbPath,
        ],
    ],
];

try {
    $db = getDb();
    $db->query('SELECT 1');
    if (!file_exists($dbPath)) {
        require __DIR__ . '/reset.php';
        exit;
    }
    http_response_code(200);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(503);
    $response['status'] = 'unhealthy';
    $response['checks']['database']['status'] = 'error';
    $response['checks']['database']['message'] = $debugMode ? $e->getMessage() : 'Database unavailable';
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
