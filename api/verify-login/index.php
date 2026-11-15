
<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

$code = $_POST['code'] ?? '';

if (!preg_match('/^\d{4}$/', (string) $code)) {
    http_response_code(400);
    exit(json_encode([
        'success' => false,
        'error' => 'Invalid code'
    ]));
}

if (
    !isset($_SESSION['login_code']) ||
    !isset($_SESSION['login_email'])
) {
    http_response_code(401);
    exit(json_encode([
        'success' => false,
        'error' => 'Login session expired'
    ]));
}

if (password_verify((string) $code, $_SESSION['login_code'])) {

    $email = $_SESSION['login_email'];

    unset($_SESSION['login_code']);
    unset($_SESSION['login_email']);

    session_regenerate_id(true);

    $_SESSION['user_email'] = $email;
    $_SESSION['logged_in'] = true;

    echo json_encode([
        'success' => true
    ]);

} else {
    http_response_code(401);

    echo json_encode([
        'success' => false,
        'error' => 'Incorrect code'
    ]);
}
