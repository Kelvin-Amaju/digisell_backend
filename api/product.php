<?php
header('Content-Type: application/json');
require_once '../config/cors.php';
require_once '../config/database.php';

$conn = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $conn->prepare("SELECT id, name, description, price, thumbnail, product_file, product_url, version FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $product = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'] ?? '',
                    'description' => $row['description'] ?? '',
                    'price' => isset($row['price']) ? (float)$row['price'] : 0,
                    'product_file' => ($row['product_file']) ? 'http://localhost/digisell-backend/uploads/files/' . $row['product_file'] : null,
                    'product_url' => $row['product_url'] ?? '',
                    'version' => ($row['version'] === 'free' || ($row['price'] ?? 0) == 0) ? 'free' : 'paid',
                    'thumbnail' => $row['thumbnail'] ? 'http://localhost/digisell-backend/uploads/' . $row['thumbnail'] : null,
                    'categories' => $row['categories'] ?? ''
                    
                ];
                error_log("Single product response: " . json_encode($product));
                echo json_encode($product);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Product not found']);
            }
            $stmt->close();
        } else {
            $free = isset($_GET['free']) ? $_GET['free'] : null;
            $query = "SELECT id, name, description, price, thumbnail, product_file, product_url, version, categories FROM products";
            if ($free === 'true') {
                $query .= " WHERE price = 0";
            } elseif ($free === 'false') {
                $query .= " WHERE price > 0";
            }

            $result = $conn->query($query);
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'] ?? '',
                    'description' => $row['description'] ?? '',
                    'price' => isset($row['price']) ? (float)$row['price'] : 0,
                    'product_file' => ($row['product_file']) ? 'http://localhost/digisell-backend/uploads/files/' . $row['product_file'] : null,
                    'version' => ($row['version'] === 'free' || ($row['price'] ?? 0) == 0) ? 'free' : 'paid',
                    'thumbnail' => $row['thumbnail'] ? 'http://localhost/digisell-backend/uploads/' . $row['thumbnail'] : null,
                    'categories' => $row['categories'] ?? ''
                ];
            }
            error_log("Products response (free=$free): " . json_encode($products));
            echo json_encode($products);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

$conn->close();
?>