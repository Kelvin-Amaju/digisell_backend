<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

$conn = getDB();
$page_url = $_SERVER['HTTP_REFERER'] ?? '';
$visitor_ip = $_SERVER['REMOTE_ADDR'] ?? '';

if ($page_url && $visitor_ip) {
    $stmt = $conn->prepare("INSERT INTO web_analytics (page_url, visitor_ip) VALUES (?, ?)");
    $stmt->bind_param("ss", $page_url, $visitor_ip);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['message' => 'Tracked']);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing data']);
}

$conn->close();