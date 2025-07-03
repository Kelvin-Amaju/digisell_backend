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

$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$price = $_POST['price'] ?? '';
$version = $_POST['version'] ?? 'paid';
$product_url = $_POST['product_url'] ?? null;

// Accept multiple categories
$category_ids = isset($_POST['category_ids']) ? $_POST['category_ids'] : [];
if (!is_array($category_ids)) {
    $category_ids = [$category_ids];
}

// Handle thumbnail upload
$thumbnail = $_FILES['thumbnail'] ?? null;
if (!$name || $price === '' || !$thumbnail || empty($category_ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

$upload_dir = realpath(__DIR__ . '/../uploads');
if (!$upload_dir) {
    mkdir(__DIR__ . '/../uploads', 0755, true);
    $upload_dir = realpath(__DIR__ . '/../uploads');
}

// Save thumbnail
$ext = pathinfo($thumbnail['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $ext;
$upload_path = $upload_dir . '/' . $filename;
if (!move_uploaded_file($thumbnail['tmp_name'], $upload_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload thumbnail']);
    exit;
}

// Handle product file upload (optional)
$product_file = null;
$files_dir = realpath(__DIR__ . '/../uploads/files');
if (!$files_dir) {
    mkdir(__DIR__ . '/../uploads/files', 0755, true);
    $files_dir = realpath(__DIR__ . '/../uploads/files');
}
if (isset($_FILES['product_file']) && $_FILES['product_file']['error'] === UPLOAD_ERR_OK) {
    $file_ext = pathinfo($_FILES['product_file']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('file_') . '.' . $file_ext;
    $file_path = $files_dir . '/' . $file_name;
    if (move_uploaded_file($_FILES['product_file']['tmp_name'], $file_path)) {
        $product_file = $file_name;
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload product file']);
        exit;
    }
}

$conn = getDB();

// Insert into products table (without category_id)
$stmt = $conn->prepare(
    "INSERT INTO products (name, description, price, version, thumbnail, product_file, product_url) VALUES (?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "ssdssss",
    $name,
    $description,
    $price,
    $version,
    $filename,
    $product_file,
    $product_url
);

if ($stmt->execute()) {
    $product_id = $stmt->insert_id;

    // Insert into product_categories table for each category
    $catStmt = $conn->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
    foreach ($category_ids as $cat_id) {
        $cat_id = intval($cat_id);
        $catStmt->bind_param("ii", $product_id, $cat_id);
        $catStmt->execute();
    }
    $catStmt->close();

    echo json_encode(['message' => 'Product added successfully']);
} else {
    // Clean up uploaded files if DB insert fails
    @unlink($upload_path);
    if ($product_file) @unlink($file_path);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add product']);
}

$stmt->close();
$conn->close();