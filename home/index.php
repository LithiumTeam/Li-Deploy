<?php

session_start();

$total_projects = 0;

$email = $_SESSION['user_email'] ?? '';

if ($email !== '') {
    $env = parse_ini_file(__DIR__ . '/../.env');

    require_once __DIR__ . '/../static/include/db.php';

    try {
        $conn = db_connect($env ?? []);
    } catch (Throwable $e) {
        error_log(
            'Home database error: ' . $e->getMessage()
        );

        $conn = null;
    }

    if ($conn !== null) {
        $stmt = $conn->prepare("
            SELECT total_projects
            FROM users
            WHERE email = ?
        ");

        if (!$stmt) {
            die('Database query failed.');
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $total_projects = (int)($user['total_projects'] ?? 0);

        $stmt->close();
        $conn->close();
    }
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="/static/image/logo.png">
    <link rel="stylesheet" href="/static/css/index.css">
    <title>Li-Deploy</title>
</head>
<body>

    <?php include __DIR__ . "/../static/include/header.php"; ?>

    <main id="app"></main>

    <?php include __DIR__ . "/../static/include/footer.php"; ?>

    <script>
        const total_projects = <?= json_encode($total_projects) ?>;
    </script>

    <script src="/static/js/app.js"></script>

</body>
</html>