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

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['id'] ?? '';

if (!$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing product ID']);
    exit;
}

$conn = getDB();
$stmt = $conn->prepare("SELECT thumbnail FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

$thumbnail = $result['thumbnail'];
$thumbnail_path = '../uploads/' . $thumbnail;

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    if ($thumbnail && file_exists($thumbnail_path)) {
        unlink($thumbnail_path);
    }
    echo json_encode(['message' => 'Product deleted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete product']);
}

$stmt->close();
$conn->close();