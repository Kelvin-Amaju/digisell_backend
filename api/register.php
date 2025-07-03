<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';
//require_once '../lib/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOTP($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->setFrom('no-reply@digisell.com', 'DigiSell');
        $mail->addAddress($email);
        $mail->Subject = 'Your OTP Code';
        $mail->Body = "Your OTP code is: $otp";
        return $mail->send();
    } catch (Exception $e) {
        return false;
    }
}

$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$name || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email']);
    exit;
}


$conn = getDB();
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
    http_response_code(409);
    echo json_encode(['error' => 'Email already registered']);
    exit;
}
$stmt->close();

$otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
$otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (name, email, password, otp_code, otp_expires) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $name, $email, $hashed_password, $otp, $otp_expires);

if ($stmt->execute()) {
    if (sendOTP($email, $otp)) {
        echo json_encode(['message' => 'OTP sent to email']);
    } else {
        // Rollback user creation on email failure
        $conn->query("DELETE FROM users WHERE email = '$email'");
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send OTP']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to register user']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

if (password_verify($password, $email)) {
    http_response_code(400);
    echo json_encode(['error' => 'Password cannot be the same as email']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters']);
    exit;
}

$stmt->close();
$conn->close();