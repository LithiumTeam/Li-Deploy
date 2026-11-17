<?php

header('Content-Type: application/json; charset=utf-8');

function response(int $status, array $data): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$env = parse_ini_file(__DIR__ . '/../../../.env');

if (!$env || empty($env['API_KEY']) || empty($env['API_BASE_URL'])) {
    response(500, [
        'success' => false,
        'message' => 'API configuration error.'
    ]);
}

$data = json_decode(file_get_contents('php://input'), true);

$projectName = $data['project_name'] ?? null;
$source = $data['source'] ?? null;

if (!$projectName || !$source) {
    response(400, [
        'success' => false,
        'message' => 'اطلاعات پروژه ناقص است.'
    ]);
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
            'message' => $result['error']['message'] ?? 'API request failed',
            'status' => $status
        ];
    }

    return [
        'success' => true,
        'message' => $result['choices'][0]['message']['content'] ?? ''
    ];
}

try {
    $result = askAgnes(
        'Check whether this PHP source code is syntactically valid. And this tip is the most important thing that you must check in source : if the source had some content that could connect to database, or list of directories or ANYTHINH ABOUT HOST, say "in-valid". Reply with exactly "valid" or "in-valid". Do not execute the code. Source code: ' . $source,
        $env
    );

    response($result['success'] ? 200 : 502, $result);

} catch (Throwable $e) {
    response(500, [
        'success' => false,
        'message' => 'Internal server error.'
    ]);
}