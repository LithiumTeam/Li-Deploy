
<?php

session_start();

$env = parse_ini_file(__DIR__ . '/../../.env');

require_once __DIR__ . '/../../PHPMailer-7.2.0/PHPMailer-7.2.0/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer-7.2.0/PHPMailer-7.2.0/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer-7.2.0/PHPMailer-7.2.0/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');

$email = $_POST['to'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Invalid email']));
}

$mail = new PHPMailer(true);

try {

    $code = (string) random_int(1000, 9999);

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    $mail->Username = $env['SENDER_EMAIL'];
    $mail->Password = $env['APP_PASSWORD'];

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom($env['SENDER_EMAIL'], 'Li-Deploy');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Li-Deploy Login';

    $mail->Body = '
        <div dir="rtl">
            <h2>به وبسایت Li-Deploy خوش آمدید!</h2>
            <p>کد ورود شما:</p>
            <h1 style="letter-spacing: 8px;">' . $code . '</h1>
        </div>
    ';

    $mail->AltBody = "Your login code: $code";

    $mail->send();

    $_SESSION['login_code'] = password_hash($code, PASSWORD_DEFAULT);
    $_SESSION['login_email'] = $email;

    echo json_encode(['success' => true]);

} catch (Exception $e) {

    http_response_code(500);

    error_log($mail->ErrorInfo);

    echo json_encode([
        'success' => false,
        'error' => 'Email sending failed'
    ]);

}
