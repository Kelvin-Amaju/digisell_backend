<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

session_start();

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

$conn = getDB();
$stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result || !password_verify($password, $result['password'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

if ($result['otp_code']) {
    http_response_code(403);
    echo json_encode(['error' => 'Account not verified']);
    exit;
}

$_SESSION['user_id'] = $result['id'];
$_SESSION['email'] = $result['email'];
$_SESSION['role'] = $result['role'] ?? 'user';

echo json_encode([
    'email' => $result['email'],
    'role' => $result['role']
]);

$stmt->close();
$conn->close();