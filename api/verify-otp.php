<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require '../config/database.php';
require_once '../lib/mailer.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$otp = $data['otp'] ?? '';

if (!$email || !$otp) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

$conn = getDB();
$stmt = $conn->prepare("SELECT otp_code, otp_expires FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result || $result['otp_code'] !== $otp || strtotime($result['otp_expires']) < time()) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or expired OTP']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET otp_code = NULL, otp_expires = NULL WHERE email = ?");
$stmt->bind_param("s", $email);
if ($stmt->execute()) {
    echo json_encode(['message' => 'Account verified']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Verification failed']);
}
$conn->close();