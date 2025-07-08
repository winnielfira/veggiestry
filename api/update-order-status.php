<?php
session_start();
header('Content-Type: application/json');

error_log("Update order status request received");
error_log("Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    error_log("Authentication failed: user_type = " . ($_SESSION['user_type'] ?? 'not set'));
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Please login as admin']);
    exit();
}

include '../config/database.php';

function updateProductSoldCount($conn, $orderId)
{
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'order_items'")->fetch();
        if (!$tableCheck) {
            error_log("order_items table not found");
            return false;
        }

        $query = "UPDATE products p 
                  SET p.sold = p.sold + (
                      SELECT COALESCE(oi.quantity, 0)
                      FROM order_items oi 
                      WHERE oi.product_id = p.id AND oi.order_id = ?
                  )
                  WHERE p.id IN (
                      SELECT DISTINCT oi.product_id 
                      FROM order_items oi 
                      WHERE oi.order_id = ?
                  )";

        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$orderId, $orderId]);

        if ($result) {
            error_log("Updated sold count for order: " . $orderId);
            return true;
        } else {
            error_log("Failed to update sold count for order: " . $orderId);
            return false;
        }
    } catch (Exception $e) {
        error_log("Error updating sold count: " . $e->getMessage());
        return false;
    }
}

$input = json_decode(file_get_contents('php://input'), true);
error_log("Input received: " . print_r($input, true));

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'No JSON data received']);
    exit();
}

$order_id = $input['id'] ?? $input['order_id'] ?? null;
$new_status = $input['status'] ?? null;
$notes = $input['notes'] ?? '';

if (!$order_id || !$new_status) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: id and status']);
    exit();
}

$valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status: ' . $new_status]);
    exit();
}

try {
    $check_query = "SELECT id, status FROM orders WHERE id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$order_id]);
    $order = $check_stmt->fetch();

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found with ID: ' . $order_id]);
        exit();
    }

    error_log("Order found: " . print_r($order, true));
    $old_status = $order['status'];

    $update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $result = $update_stmt->execute([$new_status, $order_id]);

    if (!$result) {
        throw new Exception("Failed to update order status");
    }

    error_log("Order status updated successfully from " . $old_status . " to " . $new_status);

    if ($new_status === 'delivered' && $old_status !== 'delivered') {
        $soldUpdateResult = updateProductSoldCount($conn, $order_id);
        error_log("Sold count update result: " . ($soldUpdateResult ? "success" : "failed"));
    }

    echo json_encode([
        'success' => true,
        'message' => 'Status pesanan berhasil diupdate dari ' . $old_status . ' ke ' . $new_status,
        'order_id' => $order_id,
        'old_status' => $old_status,
        'new_status' => $new_status
    ]);
} catch (Exception $e) {
    error_log("Update order status error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
