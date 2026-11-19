<?php

function db_connect(array $env): mysqli
{
    $user = (string) ($env['DB_USERNAME'] ?? '');
    $pass = (string) ($env['DB_PASSWORD'] ?? '');
    $db   = (string) ($env['DB_LAST'] ?? '');
    $port = (int) ($env['DB_PORT'] ?? 3306);

    if ($user === '' || $db === '') {
        throw new RuntimeException(
            'Database credentials are missing in .env.'
        );
    }

    $hosts = [];

    foreach (
        [
            $env['DB_HOST'] ?? null,
            'localhost',
            '127.0.0.1'
        ] as $candidate
    ) {
        if (is_string($candidate) && trim($candidate) !== '') {
            $hosts[trim($candidate)] = true;
        }
    }

    $lastError = 'No database host available.';

    foreach (array_keys($hosts) as $host) {
        try {
            $conn = @new mysqli($host, $user, $pass, $db, $port);

            if ($conn->connect_errno !== 0) {
                $lastError = $conn->connect_error;
                $conn->close();
                continue;
            }

            $conn->set_charset('utf8mb4');

            return $conn;

        } catch (Throwable $e) {
            $lastError = $e->getMessage();
        }
    }

    throw new RuntimeException(
        'Database connection failed on all hosts (' .
        implode(', ', array_keys($hosts)) .
        '): ' . $lastError
    );
}
