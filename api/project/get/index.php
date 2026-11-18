
<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);

function response(int $status, array $data): void {
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

try {

    // Authentication

    if (
        empty($_SESSION['logged_in']) ||
        empty($_SESSION['user_id']) ||
        empty($_SESSION['user_email'])
    ) {
        response(401, [
            'success' => false,
            'message' => 'Unauthorized.',
            'session' => [
                'logged_in' => $_SESSION['logged_in'] ?? null,
                'user_id' => $_SESSION['user_id'] ?? null,
                'user_email' => $_SESSION['user_email'] ?? null
            ]
        ]);
    }

    $userId = (int) $_SESSION['user_id'];
    $email = trim($_SESSION['user_email']);

    // Request data

    $data = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($data)) {
        response(400, [
            'success' => false,
            'message' => 'Invalid request.'
        ]);
    }

    $code = trim($data['code'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9]{8}$/', $code)) {
        response(400, [
            'success' => false,
            'message' => 'Invalid project code.'
        ]);
    }

    // Configuration

    $env = parse_ini_file(
        __DIR__ . '/../../../.env'
    );

    if (
        !$env ||
        empty($env['DB_USERNAME']) ||
        !isset($env['DB_PASSWORD']) ||
        empty($env['DB_LAST'])
    ) {
        response(500, [
            'success' => false,
            'message' => 'Configuration error.'
        ]);
    }

    // Database connection

    $conn = new mysqli(
        $env['DB_HOST'] ?? 'localhost',
        $env['DB_USERNAME'],
        $env['DB_PASSWORD'],
        $env['DB_LAST']
    );

    $conn->set_charset('utf8mb4');

    // Get project

    $stmt = $conn->prepare("
        SELECT
            id,
            project_name,
            manager,
            manager_id,
            project_code,
            source_path,
            project_url,
            created_at
        FROM Projects
        WHERE project_code = ?
        AND manager_id = ?
        AND manager = ?
        LIMIT 1
    ");

    $stmt->bind_param(
        'sis',
        $code,
        $userId,
        $email
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();
        $conn->close();

        response(404, [
            'success' => false,
            'message' => 'Project not found or access denied.',
            'debug' => [
                'session_user_id' => $userId,
                'session_email' => $email,
                'requested_code' => $code
            ]
        ]);
    }

    $project = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    // Success

    response(200, [
        'success' => true,
        'message' => 'Project found.',
        'project' => [
            'id' => (int) $project['id'],
            'name' => $project['project_name'],
            'manager' => $project['manager'],
            'manager_id' => (int) $project['manager_id'],
            'code' => $project['project_code'],
            'source_path' => $project['source_path'],
            'url' => $project['project_url'],
            'created_at' => $project['created_at']
        ]
    ]);

} catch (Throwable $e) {

    error_log(
        'Get project error: ' . $e->getMessage()
    );

    response(500, [
        'success' => false,
        'message' => 'Internal server error.'
    ]);

}
