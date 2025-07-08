<?php
session_start();
header('Content-Type: application/json');

error_log("Delete product request received");
error_log("Session: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    error_log("Authentication failed");
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
error_log("Delete input: " . print_r($input, true));

if (!$input || !isset($input['id'])) {
    error_log("Missing product ID");
    http_response_code(400);
    echo json_encode(['error' => 'Missing product ID']);
    exit();
}

$product_id = intval($input['id']);

if ($product_id <= 0) {
    error_log("Invalid product ID: " . $product_id);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product ID']);
    exit();
}

try {
    $check_query = "SELECT id, name FROM products WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$product_id]);
    $product = $check_stmt->fetch();

    if (!$product) {
        error_log("Product not found: " . $product_id);
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit();
    }

    error_log("Product found: " . $product['name']);

    $check_orders_query = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
    $check_orders_stmt = $conn->prepare($check_orders_query);
    $check_orders_stmt->execute([$product_id]);
    $order_count = $check_orders_stmt->fetch()['count'];

    error_log("Order count for product: " . $order_count);

    if ($order_count > 0) {
        $deactivate_query = "UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = ?";
        $deactivate_stmt = $conn->prepare($deactivate_query);
        $result = $deactivate_stmt->execute([$product_id]);

        if (!$result) {
            throw new Exception("Failed to deactivate product");
        }

        error_log("Product deactivated: " . $product['name']);

        echo json_encode([
            'success' => true,
            'message' => 'Produk dinonaktifkan karena sudah ada dalam pesanan',
            'product_id' => $product_id,
            'action' => 'deactivated'
        ]);
    } else {
        $delete_query = "DELETE FROM products WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $result = $delete_stmt->execute([$product_id]);

        if (!$result) {
            throw new Exception("Failed to delete product");
        }

        error_log("Product deleted: " . $product['name']);

        echo json_encode([
            'success' => true,
            'message' => 'Produk "' . $product['name'] . '" berhasil dihapus',
            'product_id' => $product_id,
            'action' => 'deleted'
        ]);
    }
} catch (PDOException $e) {
    error_log("Delete product error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Delete product error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
