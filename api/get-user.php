<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

session_start();

if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
    echo json_encode([
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'] ?? 'user'
    ]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
}