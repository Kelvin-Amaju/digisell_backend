<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

session_start();

// Check admin authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Handle file upload and form data
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? 0;
$thumbnail = $_FILES['thumbnail'] ?? null;

if (!$name || !$price || !$thumbnail) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate price
$price = (float)$price;
if ($price <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid price']);
    exit;
}

// Handle thumbnail upload
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}
$thumbnail_name = uniqid() . '-' . basename($thumbnail['name']);
$thumbnail_path = $upload_dir . $thumbnail_name;

if (!move_uploaded_file($thumbnail['tmp_name'], $thumbnail_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload thumbnail']);
    exit;
}

// Insert product into database
$conn = getDB();
$stmt = $conn->prepare("INSERT INTO products (name, description, price, thumbnail) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssds", $name, $description, $price, $thumbnail_name);

if ($stmt->execute()) {
    echo json_encode(['message' => 'Product created successfully']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create product']);
}

$stmt->close();
$conn->close();