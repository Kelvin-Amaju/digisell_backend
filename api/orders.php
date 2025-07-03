<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';
require '../vendor/autoload.php';

use Ramsey\Uuid\Uuid;
\Stripe\Stripe::setApiKey('your-stripe-secret-key');

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? 0;
$cart = $data['cart'] ?? [];
$total = $data['total'] ?? 0;

if (!$user_id || !$cart || !$total) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
}

$conn = getDB();
$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("id", $user_id, $total);
    $stmt->execute();
    $order_id = $conn->insert_id;

    foreach ($cart as $item) {
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
        $stmt->execute();
    }

    $license_key = Uuid::uuid4()->toString();
    $stmt = $conn->prepare("INSERT INTO license_keys (order_id, `key`) VALUES (?, ?)");
    $stmt->bind_param("is", $order_id, $license_key);
    $stmt->execute();

    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => 'Order #' . $order_id],
                'unit_amount' => $total * 100,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost:3000/order/' . $order_id,
        'cancel_url' => 'http://localhost:3000/checkout',
    ]);

    $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['sessionId' => $session->id, 'order_id' => $order_id]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Order creation failed']);
}
$conn->close();