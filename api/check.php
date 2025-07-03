<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_test_51PrfLhBIy0hoJ8IaV5545bzo5dCDN4SDoNxibEhnSZh5dtQ6sLMpOR9unYkbWVaCGPASUSJyWuZK2DeGsSEZkOl500641ZtE8S'); // Replace with your Stripe Secret Key

$conn = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$price = isset($input['price']) ? (float)$input['price'] : 0;
$name = isset($input['name']) ? trim($input['name']) : '';

if (!$product_id || !$user_id || !$price || !$name) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$frontend_url = getenv('FRONTEND_URL') ?: 'http://localhost:3000';

try {
    // Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => $name],
                'unit_amount' => (int)($price * 100), // Price in cents
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $frontend_url . '/products/' . $product_id . '?payment=success',
        'cancel_url' => $frontend_url . '/products/' . $product_id . '?payment=cancel',
        'client_reference_id' => json_encode(['user_id' => $user_id, 'product_id' => $product_id]),
    ]);

    // Return session URL
    echo json_encode(['session_url' => $session->url]);
    error_log("Checkout session created: session_id={$session->id}, product_id=$product_id, user_id=$user_id");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Checkout failed: ' . $e->getMessage()]);
    error_log("Checkout error: " . $e->getMessage());
}

$conn->close();
?>

$conn = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$price = isset($input['price']) ? (float)$input['price'] : 0;
$name = isset($input['name']) ? trim($input['name']) : '';

if (!$product_id || !$user_id || !$price || !$name) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => $name],
                'unit_amount' => (int)($price * 100), // Price in cents
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost:3000/products/' . $product_id . '?payment=success',
        'cancel_url' => 'http://localhost:3000/products/' . $product_id . '?payment=cancel',
        'client_reference_id' => json_encode(['user_id' => $user_id, 'product_id' => $product_id]),
    ]);

    // Return session URL
    echo json_encode(['session_url' => $session->url]);
    error_log("Checkout session created: session_id={$session->id}, product_id=$product_id, user_id=$user_id");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Checkout failed: ' . $e->getMessage()]);
    error_log("Checkout error: " . $e->getMessage());
}

$conn->close();
?>