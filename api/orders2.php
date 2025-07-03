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

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDB();

if ($method === 'GET') {
    $result = $conn->query("SELECT o.id, o.user_id, u.email, o.total_amount, o.status, o.created_at 
                            FROM orders o JOIN users u ON o.user_id = u.id");
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'email' => $row['email'],
            'total_amount' => (float)$row['total_amount'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }
    echo json_encode($orders);
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    $status = $data['status'] ?? '';
    if (!$id || !$status) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing fields']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Order updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update order']);
    }
    $stmt->close();
}

$conn->close();