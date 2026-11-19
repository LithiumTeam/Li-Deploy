
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

function askAgnes(string $prompt, array $env): array
{
    $ch = curl_init($env['API_BASE_URL']);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $env['API_KEY'],
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'agnes-2.5-flash',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0,
            'max_tokens' => 2000
        ])
    ]);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        return [
            'success' => false,
            'message' => $error
        ];
    }

    $result = json_decode($response, true);

    if ($status < 200 || $status >= 300) {
        return [
            'success' => false,
            'message' => $result['error']['message'] ?? 'API request failed'
        ];
    }

    return [
        'success' => true,
        'message' => trim(
            $result['choices'][0]['message']['content'] ?? ''
        )
    ];
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
            'message' => 'Unauthorized.'
        ]);
    }

    $userId = (int) $_SESSION['user_id'];
    $manager = $_SESSION['user_email'];

    // Configuration

    $env = parse_ini_file(
        __DIR__ . '/../../../.env'
    );

    if (
        !$env ||
        empty($env['API_KEY']) ||
        empty($env['API_BASE_URL']) ||
        empty($env['DB_USERNAME']) ||
        !isset($env['DB_PASSWORD']) ||
        empty($env['DB_LAST'])
    ) {
        response(500, [
            'success' => false,
            'message' => 'Configuration error.'
        ]);
    }

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

    $projectName = trim(
        $data['project_name'] ?? ''
    );

    $source = $data['source'] ?? '';

    if (
        $projectName === '' ||
        !is_string($source) ||
        trim($source) === ''
    ) {
        response(400, [
            'success' => false,
            'message' => 'اطلاعات پروژه ناقص است.'
        ]);
    }

    // Database connection

    require_once __DIR__ . '/../../../static/include/db.php';

    $conn = db_connect($env);

    // Create Projects table

    $conn->query("
        CREATE TABLE IF NOT EXISTS Projects (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_name VARCHAR(255) NOT NULL,
            manager VARCHAR(255) NOT NULL,
            manager_id INT NOT NULL,
            project_code CHAR(8) NOT NULL UNIQUE,
            source_path TEXT NOT NULL,
            project_url TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (manager_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // AI validation

    $prompt = '
Check whether this PHP source code is syntactically valid.

Reject the source if it contains functionality related to:
- Database connections or database queries
- Listing server directories
- Accessing host information
- Accessing server files or environment variables
- Unauthorized access to server resources

Do not execute the code.

Reply with exactly one of these:
valid
in-valid

Source code:
' . $source;

    $result = askAgnes($prompt, $env);

    if (!$result['success']) {
        response(502, [
            'success' => false,
            'message' => 'AI validation failed.'
        ]);
    }

    $validation = strtolower(
        trim($result['message'])
    );

    if ($validation !== 'valid') {
        response(422, [
            'success' => false,
            'message' => 'سورس پروژه معتبر نیست.',
            'validation' => 'in-valid'
        ]);
    }

    // Generate unique project code

    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    $checkCode = $conn->prepare("
        SELECT id
        FROM Projects
        WHERE project_code = ?
    ");

    do {

        $projectCode = '';

        for ($i = 0; $i < 8; $i++) {
            $projectCode .= $characters[
                random_int(0, strlen($characters) - 1)
            ];
        }

        $checkCode->bind_param(
            's',
            $projectCode
        );

        $checkCode->execute();

        $exists = $checkCode
            ->get_result()
            ->num_rows > 0;

    } while ($exists);

    $checkCode->close();

    // Create project directory

    $projectsDirectory = __DIR__ . '/../../../projects';

    if (
        !is_dir($projectsDirectory) &&
        !mkdir($projectsDirectory, 0755, true)
    ) {
        response(500, [
            'success' => false,
            'message' => 'Cannot create projects directory.'
        ]);
    }

    $projectDirectory = $projectsDirectory . '/' . $projectCode;

    if (!mkdir($projectDirectory, 0755)) {
        response(500, [
            'success' => false,
            'message' => 'Cannot create project directory.'
        ]);
    }

    // Save PHP source

    $sourcePath = $projectDirectory . '/index.php';

    if (
        file_put_contents(
            $sourcePath,
            $source,
            LOCK_EX
        ) === false
    ) {
        rmdir($projectDirectory);

        response(500, [
            'success' => false,
            'message' => 'Cannot save project source.'
        ]);
    }

    // Generate project URL

    $baseUrl = rtrim(
        $env['APP_BASE_URL'] ??
        (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
                ? 'https://'
                : 'http://') . $_SERVER['HTTP_HOST']
        ),
        '/'
    );

    $projectUrl = $baseUrl . '/projects/' . $projectCode . '/';

    // Save project in database

    $stmt = $conn->prepare("
        INSERT INTO Projects (
            project_name,
            manager,
            manager_id,
            project_code,
            source_path,
            project_url
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        'ssisss',
        $projectName,
        $manager,
        $userId,
        $projectCode,
        $sourcePath,
        $projectUrl
    );

    $stmt->execute();

    $projectId = $stmt->insert_id;

    $stmt->close();

    // Update user's project count

    $updateUser = $conn->prepare("
        UPDATE users
        SET total_projects = total_projects + 1
        WHERE id = ?
    ");

    $updateUser->bind_param('i', $userId);
    $updateUser->execute();
    $updateUser->close();

    $conn->close();

    // Success response

    response(201, [
        'success' => true,
        'message' => 'Project created successfully.',
        'project' => [
            'id' => $projectId,
            'name' => $projectName,
            'manager' => $manager,
            'manager_id' => $userId,
            'code' => $projectCode,
            'url' => $projectUrl
        ]
    ]);

} catch (Throwable $e) {

    error_log(
        'Project creation error: ' .
        $e->getMessage()
    );

    response(500, [
        'success' => false,
        'message' => 'Internal server error.'
    ]);

}
