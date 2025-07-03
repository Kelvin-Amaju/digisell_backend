<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$conn = getDB();

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Category deleted']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete category']);
        }
        $stmt->close();
        $conn->close();
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['data']['id'] ?? null;
        $name = $data['data']['name'] ?? '';

        if (!$id || !$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID or name']);
            exit;
        }

        $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Category updated']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update category']);
        }
        $stmt->close();
        $conn->close();
        exit;
    }
}

if ($method === 'GET') {
    $result = $conn->query("SELECT id, name FROM categories");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => (int)$row['id'],
            'name' => $row['name']
        ];
    }
    echo json_encode($categories);
} elseif ($method === 'POST') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    if (!$name) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing name']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Category added']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add category']);
    }
    $stmt->close();
} elseif ($method === 'DELETE') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? '';
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ID']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['message' => 'Category deleted']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete category']);
    }
    $stmt->close();
}

$conn->close();