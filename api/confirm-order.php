<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../config/database.php';

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'confirm':
            confirmOrderComplete($conn, $user_id);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Confirm Order API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function confirmOrderComplete($conn, $user_id)
{
    $order_id = $_POST['order_id'] ?? 0;

    if (!$order_id) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        return;
    }

    $stmt = $conn->prepare("
        SELECT id, status, COALESCE(user_confirmed, FALSE) as user_confirmed
        FROM orders 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        return;
    }

    if ($order['status'] !== 'delivered') {
        echo json_encode(['success' => false, 'error' => 'Order must be delivered before completion']);
        return;
    }

    if ($order['user_confirmed']) {
        echo json_encode(['success' => false, 'error' => 'Order already confirmed']);
        return;
    }

    try {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET user_confirmed = TRUE, updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");

        if ($stmt->execute([$order_id, $user_id])) {
            echo json_encode([
                'success' => true,
                'message' => 'Pesanan berhasil dikonfirmasi selesai!'
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to confirm order']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
