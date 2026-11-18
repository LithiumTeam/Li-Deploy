
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

function removeDirectory(string $path): void {
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path . '/' . $item;

        if (is_dir($itemPath)) {
            removeDirectory($itemPath);
        } else {
            unlink($itemPath);
        }
    }

    rmdir($path);
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
    $email = trim($_SESSION['user_email']);

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
        SELECT id, source_path
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
            'message' => 'Project not found or access denied.'
        ]);
    }

    $project = $result->fetch_assoc();

    $stmt->close();

    removeDirectory(dirname($project['source_path']));

    $delete = $conn->prepare("
        DELETE FROM Projects
        WHERE id = ?
    ");

    $projectId = (int) $project['id'];

    $delete->bind_param('i', $projectId);
    $delete->execute();
    $delete->close();

    $updateUser = $conn->prepare("
        UPDATE users
        SET total_projects = GREATEST(total_projects - 1, 0)
        WHERE id = ?
    ");

    $updateUser->bind_param('i', $userId);
    $updateUser->execute();
    $updateUser->close();

    $conn->close();

    response(200, [
        'success' => true,
        'message' => 'Project deleted successfully.'
    ]);

} catch (Throwable $e) {

    error_log(
        'Delete project error: ' . $e->getMessage()
    );

    response(500, [
        'success' => false,
        'message' => 'Internal server error.'
    ]);

}
