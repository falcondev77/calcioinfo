<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/fetcher.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $report = fetcher_run();
    echo json_encode(['ok' => true, 'report' => $report], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
