<?php
session_start();
header('Content-Type: application/json');

error_log("Add product request received");
error_log("Session: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    error_log("Authentication failed");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
error_log("Add product input: " . print_r($input, true));

if (!$input || !isset($input['name']) || !isset($input['price'])) {
    error_log("Missing required fields");
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields (name, price)']);
    exit();
}

$name = trim($input['name']);
$description = trim($input['description'] ?? '');
$price = floatval($input['price']);
$stock = intval($input['stock'] ?? 0);
$category = trim($input['category'] ?? '');
$image_url = trim($input['image_url'] ?? '');

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Product name is required']);
    exit();
}

if ($price <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Price must be greater than 0']);
    exit();
}

if ($stock < 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Stock cannot be negative']);
    exit();
}

try {
    $check_query = "SELECT id FROM products WHERE name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$name]);

    if ($check_stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Product with this name already exists']);
        exit();
    }

    $insert_query = "INSERT INTO products (name, description, price, stock, category, image_url, is_active, created_at, updated_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())";

    $stmt = $conn->prepare($insert_query);
    $result = $stmt->execute([$name, $description, $price, $stock, $category, $image_url]);

    if (!$result) {
        throw new Exception("Failed to insert product");
    }

    $product_id = $conn->lastInsertId();
    error_log("Product added successfully with ID: " . $product_id);

    echo json_encode([
        'success' => true,
        'message' => 'Produk "' . $name . '" berhasil ditambahkan',
        'product_id' => $product_id
    ]);
} catch (PDOException $e) {
    error_log("Add product error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Add product error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
