<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

// Load Stripe Secret Key from environment variable
\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY')); // Uses .env or server env variable

$conn = getDB();
$input = file_get_contents('php://input');
$payload = json_decode($input, true);
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$endpoint_secret = getenv('STRIPE_WEBHOOK_SECRET'); // Uses .env or server env variable

try {
    $event = \Stripe\Webhook::constructEvent($input, $sig_header, $endpoint_secret);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Webhook error: ' . $e->getMessage()]);
    exit;
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $client_reference = json_decode($session->client_reference_id, true);
    $user_id = (int)($client_reference['user_id'] ?? 0);
    $product_id = (int)($client_reference['product_id'] ?? 0);
    $payment_id = $session->payment_intent;

    if ($user_id && $product_id) {
        $stmt = $conn->prepare("INSERT INTO purchases (user_id, product_id, payment_id) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iis", $user_id, $product_id, $payment_id);
            $stmt->execute();
            $stmt->close();
            error_log("Purchase recorded: user_id=$user_id, product_id=$product_id, payment_id=$payment_id");
        }
    }
}

http_response_code(200);
echo json_encode(['status' => 'success']);
$conn->close();
?>