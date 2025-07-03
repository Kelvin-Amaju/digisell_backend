<?php
header('Content-Type: application/json');
require_once '/config/cors.php';
require_once '/config/database.php';

session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = getDB();

// Sales Stats
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'")->fetch_assoc()['total'] ?? 0;
$order_count = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status != 'cancelled'")->fetch_assoc()['count'] ?? 0;
$avg_order_value = $order_count > 0 ? $total_revenue / $order_count : 0;

// Recent Orders
$recent_orders = [];
$result = $conn->query("SELECT o.id, u.email, o.total_amount, o.status, o.created_at 
                        FROM orders o JOIN users u ON o.user_id = u.id 
                        ORDER BY o.created_at DESC LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $recent_orders[] = [
        'id' => (int)$row['id'],
        'email' => $row['email'],
        'total_amount' => (float)$row['total_amount'],
        'status' => $row['status'],
        'created_at' => $row['created_at']
    ];
}

// Low Stock Products
$low_stock = [];
$result = $conn->query("SELECT id, name, stock FROM products WHERE stock <= 10 LIMIT 5");
while ($row = $result->fetch_assoc()) {
    $low_stock[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'stock' => (int)$row['stock']
    ];
}

// User Stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
$new_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'] ?? 0;
$admin_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'] ?? 0;

// Web Stats (basic, assumes web_analytics populated)
$page_views = $conn->query("SELECT COUNT(*) as count FROM web_analytics")->fetch_assoc()['count'] ?? 0;
$unique_visitors = $conn->query("SELECT COUNT(DISTINCT visitor_ip) as count FROM web_analytics")->fetch_assoc()['count'] ?? 0;

echo json_encode([
    'sales' => [
        'total_revenue' => (float)$total_revenue,
        'order_count' => (int)$order_count,
        'avg_order_value' => (float)$avg_order_value
    ],
    'users' => [
        'total' => (int)$total_users,
        'new' => (int)$new_users,
        'admins' => (int)$admin_users
    ],
    'web' => [
        'page_views' => (int)$page_views,
        'unique_visitors' => (int)$unique_visitors
    ],
    'recent_orders' => $recent_orders,
    'low_stock' => $low_stock
]);

$conn->close();