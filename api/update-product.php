<?php
session_start();
header('Content-Type: application/json');

error_log("Update product request received");
error_log("Session: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    error_log("Authentication failed");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
error_log("Update product input: " . print_r($input, true));

if (!$input || !isset($input['id']) || !isset($input['name']) || !isset($input['price'])) {
    error_log("Missing required fields");
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields (id, name, price)']);
    exit();
}

$product_id = intval($input['id']);
$name = trim($input['name']);
$description = trim($input['description'] ?? '');
$price = floatval($input['price']);
$stock = intval($input['stock'] ?? 0);
$category = trim($input['category'] ?? '');
$image_url = trim($input['image_url'] ?? '');

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit();
}

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
    $check_query = "SELECT id, name FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$product_id]);
    $existing_product = $check_stmt->fetch();

    if (!$existing_product) {
        error_log("Product not found: " . $product_id);
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit();
    }

    $check_name_query = "SELECT id FROM products WHERE name = ? AND id != ?";
    $check_name_stmt = $conn->prepare($check_name_query);
    $check_name_stmt->execute([$name, $product_id]);

    if ($check_name_stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Another product with this name already exists']);
        exit();
    }

    $update_query = "UPDATE products SET 
                        name = ?, 
                        description = ?, 
                        price = ?, 
                        stock = ?, 
                        category = ?, 
                        image_url = ?, 
                        updated_at = NOW() 
                     WHERE id = ?";

    $stmt = $conn->prepare($update_query);
    $result = $stmt->execute([$name, $description, $price, $stock, $category, $image_url, $product_id]);

    if (!$result) {
        throw new Exception("Failed to update product");
    }

    error_log("Product updated successfully: " . $name);

    echo json_encode([
        'success' => true,
        'message' => 'Produk "' . $name . '" berhasil diupdate',
        'product_id' => $product_id
    ]);
} catch (PDOException $e) {
    error_log("Update product error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Update product error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
