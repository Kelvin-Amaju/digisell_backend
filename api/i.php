<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *"); // Adjust for security

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    http_response_code(401);
    echo json_encode(["error" => "Missing credentials"]);
    exit;
}

// Connect to DB
$conn = new mysqli('localhost', 'root', '', 'digisell');

$stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        echo json_encode(["message" => "Login successful"]);
    } else {
        http_response_code(401);
        echo json_encode(["error" => "Invalid password"]);
    }
} else {
    http_response_code(401);
    echo json_encode(["error" => "User not found"]);
}
?>