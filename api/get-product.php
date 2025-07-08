<?php
session_start();
header('Content-Type: application/json');

error_log("Get product request received");
error_log("GET params: " . print_r($_GET, true));
error_log("Session: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    error_log("Authentication failed");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    error_log("Invalid product ID: " . ($_GET['id'] ?? 'not set'));
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit();
}

$product_id = $_GET['id'];
error_log("Fetching product with ID: " . $product_id);

try {
    $query = "SELECT p.*, 
                     (p.stock - COALESCE(p.sold, 0)) as available_stock,
                     c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              WHERE p.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        error_log("Product not found with ID: " . $product_id);
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit();
    }

    $product['available_stock'] = max(0, intval($product['available_stock']));
    $product['is_out_of_stock'] = ($product['available_stock'] <= 0);
    $product['is_low_stock'] = ($product['available_stock'] > 0 && $product['available_stock'] <= 5);

    error_log("Product found: " . $product['name'] . " (Available stock: " . $product['available_stock'] . ")");
    echo json_encode($product);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
