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
$category_id = $_POST['category_id'] ?? null;
$product_id = $_POST['id'] ?? '';
$version = $_POST['version'] ?? 'paid';
$product_url = $_POST['product_url'] ?? null;
$thumbnail = $_FILES['thumbnail'] ?? null;

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

if (!$name || $price === '' || !$product_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

$conn = getDB();
$stmt = $conn->prepare("SELECT thumbnail, product_file FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found']);
    exit;
}

$old_thumbnail = $result['thumbnail'];
$old_product_file = $result['product_file'];
$filename = $old_thumbnail;

// Handle thumbnail upload
if ($thumbnail && $thumbnail['error'] === UPLOAD_ERR_OK) {
    $upload_dir = realpath(__DIR__ . '/../uploads');
    if (!$upload_dir) {
        mkdir(__DIR__ . '/../uploads', 0755, true);
        $upload_dir = realpath(__DIR__ . '/../uploads');
    }
    $ext = pathinfo($thumbnail['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $upload_path = $upload_dir . '/' . $filename;

    if (!move_uploaded_file($thumbnail['tmp_name'], $upload_path)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to upload thumbnail']);
        exit;
    }

    if ($old_thumbnail && file_exists($upload_dir . '/' . $old_thumbnail)) {
        unlink($upload_dir . '/' . $old_thumbnail);
    }
}

// If a new product file was uploaded, remove the old one
if ($product_file && $old_product_file && file_exists($files_dir . '/' . $old_product_file)) {
    unlink($files_dir . '/' . $old_product_file);
}

// Build the update query dynamically
$fields = "name = ?, description = ?, price = ?, category_id = ?, version = ?, thumbnail = ?";
$params = [$name, $description, $price, $category_id, $version, $filename];
$types = "ssdiss";

if ($product_file !== null) {
    $fields .= ", product_file = ?";
    $params[] = $product_file;
    $types .= "s";
}
if ($product_url !== null) {
    $fields .= ", product_url = ?";
    $params[] = $product_url;
    $types .= "s";
}

$fields .= " WHERE id = ?";
$params[] = $product_id;
$types .= "i";

$stmt = $conn->prepare("UPDATE products SET $fields");
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(['message' => 'Product updated successfully']);
} else {
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update product']);
}

$stmt->close();
$conn->close();