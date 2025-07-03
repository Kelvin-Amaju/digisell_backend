<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

session_start();

// Log function for debugging
function logError($message) {
    file_put_contents('../debug.log', date('Y-m-d H:i:s') . " [STATS] $message\n", FILE_APPEND);
}

// Check admin session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    logError("Unauthorized access: user_id=" . ($_SESSION['user_id'] ?? 'none') . ", role=" . ($_SESSION['role'] ?? 'none'));
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = getDB();
if (!$conn) {
    logError("Database connection failed");
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Check if tables exist
$required_tables = ['orders', 'users', 'products', 'web_analytics'];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        logError("Table $table does not exist");
        http_response_code(500);
        echo json_encode(['error' => "Table $table does not exist"]);
        exit;
    }
}

// Sales Stats
$total_revenue_result = $conn->query("SELECT SUM(total) as total FROM orders WHERE status != 'cancelled'");
if (!$total_revenue_result) {
    logError("Query failed: SELECT SUM(total) - " . $conn->error);
}
$total_revenue = $total_revenue_result ? ($total_revenue_result->fetch_assoc()['total'] ?? 0) : 0;

$order_count_result = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status != 'cancelled'");
if (!$order_count_result) {
    logError("Query failed: SELECT COUNT(*) orders - " . $conn->error);
}
$order_count = $order_count_result ? ($order_count_result->fetch_assoc()['count'] ?? 0) : 0;

$avg_order_value = $order_count > 0 ? $total_revenue / $order_count : 0;

// Recent Orders
$recent_orders = [];
$result = $conn->query("SELECT o.id, u.email, o.total, o.status, o.created_at 
                        FROM orders o LEFT JOIN users u ON o.user_id = u.id 
                        ORDER BY o.created_at DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = [
            'id' => (int)$row['id'],
            'email' => $row['email'] ?? 'Unknown',
            'total' => (float)($row['total'] ?? 0),
            'status' => $row['status'] ?? 'Unknown',
            'created_at' => $row['created_at'] ?? date('Y-m-d H:i:s')
        ];
    }
} else {
    logError("Query failed: SELECT recent orders - " . $conn->error);
}

// Low Stock Products
$low_stock = [];
$result = $conn->query("SELECT id, name, stock FROM products WHERE stock <= 10 LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $low_stock[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'] ?? 'Unknown',
            'stock' => (int)($row['stock'] ?? 0)
        ];
    }
} else {
    logError("Query failed: SELECT low stock - " . $conn->error);
}

// User Stats
$total_users_result = $conn->query("SELECT COUNT(*) as count FROM users");
if (!$total_users_result) {
    logError("Query failed: SELECT COUNT(*) users - " . $conn->error);
}
$total_users = $total_users_result ? ($total_users_result->fetch_assoc()['count'] ?? 0) : 0;

$new_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
if (!$new_users_result) {
    logError("Query failed: SELECT new users - " . $conn->error);
}
$new_users = $new_users_result ? ($new_users_result->fetch_assoc()['count'] ?? 0) : 0;

$admin_users_result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
if (!$admin_users_result) {
    logError("Query failed: SELECT admin users - " . $conn->error);
}
$admin_users = $admin_users_result ? ($admin_users_result->fetch_assoc()['count'] ?? 0) : 0;

// Web Stats
$page_views_result = $conn->query("SELECT COUNT(*) as count FROM web_analytics");
if (!$page_views_result) {
    logError("Query failed: SELECT page views - " . $conn->error);
}
$page_views = $page_views_result ? ($page_views_result->fetch_assoc()['count'] ?? 0) : 0;

$unique_visitors_result = $conn->query("SELECT COUNT(DISTINCT visitors_ip) as count FROM web_analytics");
if (!$unique_visitors_result) {
    logError("Query failed: SELECT unique visitors - " . $conn->error);
}
$unique_visitors = $unique_visitors_result ? ($unique_visitors_result->fetch_assoc()['count'] ?? 0) : 0;

$response = [
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
];

echo json_encode($response);
logError("Response sent: " . json_encode($response));

$conn->close();