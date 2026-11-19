<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

$env = parse_ini_file(__DIR__ . '/../../.env');

$report = [
    'env_loaded' => is_array($env),
    'configured_host' => $env['DB_HOST'] ?? null,
    'configured_port' => $env['DB_PORT'] ?? null,
    'database' => $env['DB_LAST'] ?? null,
    'username' => $env['DB_USERNAME'] ?? null
];

require_once __DIR__ . '/../../static/include/db.php';

try {
    $conn = db_connect($env ?? []);

    $report['connected'] = true;
    $report['mysql_server'] = $conn->server_info;
    $report['connected_host'] = $conn->host_info;

    $tables = [];

    if ($result = $conn->query('SHOW TABLES')) {
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
    }

    $report['tables'] = $tables;

    $conn->close();

} catch (Throwable $e) {
    $report['connected'] = false;
    $report['error'] = $e->getMessage();
}

echo json_encode(
    $report,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
