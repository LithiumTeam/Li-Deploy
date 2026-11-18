
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

    if (
        empty($_SESSION['logged_in']) ||
        empty($_SESSION['user_id']) ||
        empty($_SESSION['user_email'])
    ) {
        response(401, [
            'success' => false,
            'message' => 'Unauthorized.'
        ]);
    }

    $userId = (int) $_SESSION['user_id'];
    $email = $_SESSION['user_email'];

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

    $conn = new mysqli(
        $env['DB_HOST'] ?? 'localhost',
        $env['DB_USERNAME'],
        $env['DB_PASSWORD'],
        $env['DB_LAST']
    );

    $conn->set_charset('utf8mb4');

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM Projects
        WHERE manager_id = ?
        AND manager = ?
    ");

    $stmt->bind_param(
        'is',
        $userId,
        $email
    );

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $totalProjects = (int) $row['total'];

    $stmt->close();

    $projectCode = null;

    if ($totalProjects > 0) {

        $stmt = $conn->prepare("
            SELECT project_code
            FROM Projects
            WHERE manager_id = ?
            AND manager = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->bind_param(
            'is',
            $userId,
            $email
        );

        $stmt->execute();

        $result = $stmt->get_result();
        $project = $result->fetch_assoc();

        $projectCode = $project['project_code'] ?? null;

        $stmt->close();
    }

    $conn->close();

    response(200, [
        'success' => true,
        'user_email' => $email,
        'has_projects' => $totalProjects > 0,
        'total_projects' => $totalProjects,
        'project_code' => $projectCode
    ]);

} catch (Throwable $e) {

    error_log(
        'Project check error: ' . $e->getMessage()
    );

    response(500, [
        'success' => false,
        'message' => 'Internal server error.'
    ]);

}
