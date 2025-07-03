<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = getDB();

// Count new orders (e.g., status = 'pending' or 'new')
$newOrders = 0;
$orderResult = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending' OR status = 'new'");
if ($orderResult && $row = $orderResult->fetch_assoc()) {
    $newOrders = (int)$row['count'];
}

// Count new users (e.g., registered in the last 24 hours)
$newUsers = 0;
$userResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= NOW() - INTERVAL 1 DAY");
if ($userResult && $row = $userResult->fetch_assoc()) {
    $newUsers = (int)$row['count'];
}

echo json_encode([
    "new_orders" => $newOrders,
    "new_users" => $newUsers
]);

$conn->close();