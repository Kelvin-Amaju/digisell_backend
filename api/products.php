<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

$conn = getDB();
$free = isset($_GET['free']) ? $_GET['free'] : null;

if ($free === 'true') {
    $result = $conn->query("SELECT id, name, description, price, thumbnail, version FROM products WHERE price = 0");
} elseif ($free === 'false') {
    $result = $conn->query("SELECT id, name, description, price, thumbnail, version FROM products WHERE price > 0");
} else {
    $result = $conn->query("SELECT id, name, description, price, thumbnail, version FROM products");
}

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'price' => (float)$row['price'],
        'version' => $row['version'] === 'free' ? 'free' : 'paid',
        'thumbnail' => $row['thumbnail'] ? 'http://localhost/digisell-backend/uploads/' . $row['thumbnail'] : null,
        'categories' => $row['categories'] ?? ''
    ];
}

echo json_encode($products);
$conn->close();