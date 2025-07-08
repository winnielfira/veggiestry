<?php
header('Content-Type: application/json');
session_start();
include '../config/database.php';
include '../includes/functions.php';

$action = $_GET['action'] ?? 'get';
$order_id = $_GET['id'] ?? $_POST['id'] ?? $_GET['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    switch ($action) {
        case 'getAll':
            getAllOrders($conn);
            break;

        case 'getDetails':
            getOrderDetails($conn);
            break;

        case 'updateStatus':
            updateOrderStatus($conn);
            break;

        case 'exportPDF':
            exportOrderToPDF($conn);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Order Details API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getOrderDetailsEnhanced($conn, $order_id, $user_id, $is_admin)
{
    try {
        $whereClause = $is_admin ? "o.id = ?" : "o.id = ? AND o.user_id = ?";
        $params = $is_admin ? [$order_id] : [$order_id, $user_id];

        $orderQuery = "
            SELECT o.*, 
                   u.full_name as customer_name, 
                   u.email as customer_email, 
                   u.phone as customer_phone,
                   -- Calculate shipping cost based on business rule
                   CASE 
                       WHEN o.shipping_cost IS NOT NULL THEN o.shipping_cost
                       WHEN o.total_amount >= 100000 THEN 0
                       ELSE 15000
                   END as calculated_shipping_cost,
                   -- Calculate grand total
                   (o.total_amount + 
                    CASE 
                        WHEN o.shipping_cost IS NOT NULL THEN o.shipping_cost
                        WHEN o.total_amount >= 100000 THEN 0
                        ELSE 15000
                    END) as grand_total
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE $whereClause
        ";

        $orderStmt = $conn->prepare($orderQuery);
        $orderStmt->execute($params);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
            return;
        }

        $order['shipping_cost'] = floatval($order['calculated_shipping_cost']);
        $order['total_amount'] = floatval($order['total_amount']);
        $order['grand_total'] = floatval($order['grand_total']);

        $itemsQuery = "
            SELECT oi.*, 
                   p.name as product_name, 
                   p.slug,
                   p.category,
                   p.description as product_description,
                   -- Ensure we get the correct price field
                   COALESCE(oi.price, oi.product_price, p.price) as unit_price,
                   -- Calculate item total
                   (COALESCE(oi.price, oi.product_price, p.price) * oi.quantity) as item_total
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ";

        $itemsStmt = $conn->prepare($itemsQuery);
        $itemsStmt->execute([$order_id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $processedItems = [];
        $itemsSubTotal = 0;

        foreach ($items as $item) {
            $image_url = '';
            if (!empty($item['slug'])) {
                $image_url = $item['slug'] . '.jpg';
            } else if (!empty($item['product_name'])) {
                $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $item['product_name']));
                $image_url = $clean_name . '.jpg';
            }

            $processed = [
                'id' => intval($item['id']),
                'product_id' => intval($item['product_id']),
                'product_name' => $item['product_name'] ?: 'Unknown Product',
                'product_description' => $item['product_description'] ?: '',
                'category' => $item['category'] ?: '',
                'image_url' => $image_url, 
                'quantity' => intval($item['quantity']),
                'unit_price' => floatval($item['unit_price']),
                'item_total' => floatval($item['item_total']),
                'created_at' => $item['created_at'] ?? null
            ];

            $itemsSubTotal += $processed['item_total'];
            $processedItems[] = $processed;
        }

        $history = [];
        if ($is_admin) {
            try {
                $historyQuery = "
                    SELECT osh.*, 
                           u.full_name as updated_by_name,
                           u.email as updated_by_email
                    FROM order_status_history osh
                    LEFT JOIN users u ON osh.created_by = u.id
                    WHERE osh.order_id = ?
                    ORDER BY osh.created_at DESC
                ";

                $historyStmt = $conn->prepare($historyQuery);
                $historyStmt->execute([$order_id]);
                $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                error_log("Order status history error: " . $e->getMessage());
            }
        }

        $summary = [
            'items_count' => count($processedItems),
            'unique_products' => count(array_unique(array_column($processedItems, 'product_id'))),
            'total_quantity' => array_sum(array_column($processedItems, 'quantity')),
            'items_subtotal' => $itemsSubTotal,
            'shipping_cost' => $order['shipping_cost'],
            'grand_total' => $order['grand_total'],
            'is_free_shipping' => $order['shipping_cost'] == 0,
            'shipping_threshold' => 100000
        ];

        $order['created_at_formatted'] = date('d M Y H:i', strtotime($order['created_at']));
        $order['updated_at_formatted'] = $order['updated_at'] ? date('d M Y H:i', strtotime($order['updated_at'])) : null;

        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $processedItems,
            'summary' => $summary,
            'history' => $history,
            'user_permissions' => [
                'can_edit' => $is_admin,
                'can_view_history' => $is_admin,
                'can_export' => true
            ]
        ]);
    } catch (Exception $e) {
        error_log("Error in getOrderDetailsEnhanced: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred while fetching order details'
        ]);
    }
}

function validateOrderId($order_id)
{
    if (empty($order_id)) {
        return false;
    }

    if (strpos($order_id, 'VG') === 0) {
        $order_id = substr($order_id, 2);
    }

    $order_id = ltrim($order_id, '0');

    return filter_var($order_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
}

function formatCurrency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function calculateShippingCost($totalAmount, $storedShippingCost = null)
{
    if ($storedShippingCost !== null) {
        return floatval($storedShippingCost);
    }

    return ($totalAmount >= 100000) ? 0 : 15000;
}
