<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

$conn = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if (!$user_id || !$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user_id or product_id']);
    exit;
}

$stmt = $conn->prepare("SELECT id FROM purchases WHERE user_id = ? AND product_id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

$response = ['purchased' => $result->num_rows > 0];
error_log("Purchase check: user_id=$user_id, product_id=$product_id, purchased=" . ($response['purchased'] ? 'true' : 'false'));
echo json_encode($response);

$stmt->close();
$conn->close();
?>