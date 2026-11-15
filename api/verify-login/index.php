<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

function response(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}


$env = parse_ini_file(__DIR__ . '/../../.env');
$code = $_POST['code'] ?? '';

if (!preg_match('/^\d{4}$/', (string) $code)) {
    response(400, [
        'success' => false,
        'error' => 'Invalid code'
    ]);
}

if (
    !isset($_SESSION['login_code']) ||
    !isset($_SESSION['login_email'])
) {
    response(401, [
        'success' => false,
        'error' => 'Login session expired'
    ]);
}

if (!password_verify(
    (string) $code,
    $_SESSION['login_code']
)) {
    response(401, [
        'success' => false,
        'error' => 'Incorrect code'
    ]);
}

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

try {

    $conn = new mysqli(
        'localhost',
        $env['DB_USERNAME'],
        $env['DB_PASSWORD'],
        $env['DB_LAST']
    );

    $conn->set_charset('utf8mb4');

    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $email = $_SESSION['login_email'];

    $stmt = $conn->prepare("
        INSERT INTO users (email)
        VALUES (?)
        ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
    ");

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $user_id = $conn->insert_id;

    $stmt->close();
    $conn->close();

    session_regenerate_id(true);

    unset($_SESSION['login_code']);
    unset($_SESSION['login_email']);

    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['logged_in'] = true;

    response(200, [
        'success' => true,
        'user_id' => $user_id
    ]);

} catch (Throwable $e) {

    error_log(
        'Login verification error: ' .
        $e->getMessage()
    );

    response(500, [
        'success' => false,
        'error' => 'Database error'
    ]);

}