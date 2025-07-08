<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../config/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'getAll';

try {
    switch ($action) {
        case 'getAll':
            getUserOrders($conn);
            break;

        case 'get':
            getOrderDetails($conn);
            break;

        case 'cancel':
            cancelOrder($conn);
            break;

        default:
            getUserOrders($conn);
            break;
    }
} catch (Exception $e) {
    error_log("User Orders API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function getUserOrders($conn)
{
    $limit = intval($_GET['limit'] ?? 20);
    $offset = intval($_GET['offset'] ?? 0);

    try {
        $query = "SELECT 
                    o.id, 
                    o.order_number,
                    o.total_amount, 
                    o.shipping_cost,
                    o.grand_total,
                    o.status, 
                    o.payment_status,
                    o.payment_method,
                    o.notes,
                    o.created_at,
                    o.updated_at,
                    COUNT(oi.id) as total_items
                  FROM orders o
                  LEFT JOIN order_items oi ON o.id = oi.order_id
                  WHERE o.user_id = ?
                  GROUP BY o.id, o.order_number, o.total_amount, o.shipping_cost, 
                           o.grand_total, o.status, o.payment_status, o.payment_method, 
                           o.notes, o.created_at, o.updated_at
                  ORDER BY o.created_at DESC
                  LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $limit, $offset]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countQuery = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
        $countStmt = $conn->prepare($countQuery);
        $countStmt->execute([$_SESSION['user_id']]);
        $totalOrders = $countStmt->fetch()['total'];

        foreach ($orders as &$order) {
            $display_total = $order['grand_total'] ?: ($order['total_amount'] + ($order['shipping_cost'] ?: 0));

            $order['formatted_date'] = date('d M Y H:i', strtotime($order['created_at']));
            $order['formatted_amount'] = number_format($display_total, 0, ',', '.');
            $order['can_cancel'] = ($order['status'] === 'pending');
        }

        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'total' => $totalOrders,
            'limit' => $limit,
            'offset' => $offset
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getOrderDetails($conn)
{
    $orderId = $_GET['id'] ?? '';

    if (empty($orderId)) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        return;
    }

    try {
        $orderQuery = "SELECT * FROM orders WHERE id = ? AND user_id = ?";
        $orderStmt = $conn->prepare($orderQuery);
        $orderStmt->execute([$orderId, $_SESSION['user_id']]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            return;
        }

        $itemsQuery = "SELECT 
                        oi.*, 
                        p.name as product_name, 
                        p.image as product_image,
                        p.slug as product_slug
                       FROM order_items oi
                       LEFT JOIN products p ON oi.product_id = p.id
                       WHERE oi.order_id = ?
                       ORDER BY oi.id";

        $itemsStmt = $conn->prepare($itemsQuery);
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $order['formatted_date'] = date('d M Y H:i', strtotime($order['created_at']));
        $order['formatted_amount'] = number_format($order['total_amount'], 0, ',', '.');
        $order['formatted_shipping'] = number_format($order['shipping_cost'], 0, ',', '.');
        $order['grand_total'] = $order['total_amount'] + $order['shipping_cost'];
        $order['formatted_grand_total'] = number_format($order['grand_total'], 0, ',', '.');

        foreach ($items as &$item) {
            $item['formatted_price'] = number_format($item['product_price'], 0, ',', '.');
            $item['formatted_subtotal'] = number_format($item['subtotal'], 0, ',', '.');
        }

        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items
        ]);
    } catch (Exception $e) {
        throw $e;
    }
}

function cancelOrder($conn)
{
    $orderId = $_POST['order_id'] ?? '';

    if (empty($orderId)) {
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        return;
    }

    try {
        $checkQuery = "SELECT id, status FROM orders WHERE id = ? AND user_id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$orderId, $_SESSION['user_id']]);
        $order = $checkStmt->fetch();

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            return;
        }

        if ($order['status'] !== 'pending') {
            echo json_encode(['success' => false, 'error' => 'Hanya pesanan dengan status pending yang dapat dibatalkan']);
            return;
        }

        $conn->beginTransaction();

        try {
            $updateQuery = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateResult = $updateStmt->execute([$orderId]);

            if (!$updateResult) {
                throw new Exception('Failed to cancel order');
            }

            $itemsQuery = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
            $itemsStmt = $conn->prepare($itemsQuery);
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll();

            foreach ($items as $item) {
                $restoreStockQuery = "UPDATE products SET stock = stock + ? WHERE id = ?";
                $restoreStockStmt = $conn->prepare($restoreStockQuery);
                $restoreStockStmt->execute([$item['quantity'], $item['product_id']]);
            }

            $conn->commit();

            echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibatalkan']);
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    } catch (Exception $e) {
        throw $e;
    }
}
