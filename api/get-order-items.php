<?php
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

if (!$user_id && !$is_admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../config/database.php';

$action = $_GET['action'] ?? 'get';
$order_id = $_GET['order_id'] ?? $_GET['id'] ?? 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID is required']);
    exit();
}

try {
    switch ($action) {
        case 'get':
        case 'getItems':
            getOrderItems($conn, $order_id, $user_id, $is_admin);
            break;
        case 'getReviewableItems':
            getReviewableItems($conn, $order_id, $user_id);
            break;
        case 'getItemsSummary':
            getOrderItemsSummary($conn, $order_id, $user_id, $is_admin);
            break;
        default:
            getOrderItems($conn, $order_id, $user_id, $is_admin);
            break;
    }
} catch (Exception $e) {
    error_log("Order Items API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function getOrderItems($conn, $order_id, $user_id, $is_admin)
{
    try {
        $orderCheckQuery = $is_admin
            ? "SELECT id, status, user_id, total_amount FROM orders WHERE id = ?"
            : "SELECT id, status, user_id, total_amount, COALESCE(user_confirmed, FALSE) as user_confirmed FROM orders WHERE id = ? AND user_id = ?";

        $orderCheckParams = $is_admin ? [$order_id] : [$order_id, $user_id];

        $stmt = $conn->prepare($orderCheckQuery);
        $stmt->execute($orderCheckParams);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found or access denied']);
            return;
        }

        $itemsQuery = "
            SELECT 
                oi.id as item_id,
                oi.order_id,
                oi.product_id,
                oi.quantity,
                -- Use the most appropriate price field
                COALESCE(oi.price, oi.product_price, p.price) as unit_price,
                -- Calculate item total
                (COALESCE(oi.price, oi.product_price, p.price) * oi.quantity) as item_total,
                oi.created_at as item_created_at,
                -- Product details (based on actual table structure)
                p.name as product_name,
                p.description as product_description,
                p.slug,
                c.name as category_name,
                p.rating as current_rating,
                p.review_count,
                p.stock as current_stock,
                p.is_featured,
                p.status as product_status,
                -- Check if already reviewed (for customer use)
                CASE 
                    WHEN r.id IS NOT NULL THEN 1 
                    ELSE 0 
                END as already_reviewed,
                r.rating as review_rating,
                r.comment as review_comment,
                r.created_at as review_date
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN reviews r ON r.product_id = p.id AND r.user_id = ? AND r.order_id = ?
            WHERE oi.order_id = ?
            ORDER BY oi.id ASC
        ";

        $stmt = $conn->prepare($itemsQuery);
        $stmt->execute([$user_id, $order_id, $order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processedItems = [];
        $summary = [
            'total_items' => 0,
            'total_quantity' => 0,
            'subtotal' => 0,
            'unique_products' => 0,
            'reviewable_count' => 0
        ];

        foreach ($items as $item) {
            $image_url = '';
            if (!empty($item['slug'])) {
                $image_url = $item['slug'] . '.jpg';
            } else if (!empty($item['product_name'])) {
                $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $item['product_name']));
                $image_url = $clean_name . '.jpg';
            }

            $processed = [
                'item_id' => intval($item['item_id']),
                'product_id' => intval($item['product_id']),
                'product_name' => $item['product_name'] ?: 'Unknown Product',
                'product_description' => $item['product_description'] ?: '',
                'category' => $item['category_name'] ?: 'Uncategorized',
                'image_url' => $image_url,
                'quantity' => intval($item['quantity']),
                'unit_price' => floatval($item['unit_price']),
                'item_total' => floatval($item['item_total']),
                'current_rating' => $item['current_rating'] ? floatval($item['current_rating']) : 0,
                'review_count' => intval($item['review_count'] ?: 0),
                'current_stock' => intval($item['current_stock'] ?: 0),
                'is_featured' => intval($item['is_featured'] ?: 0),
                'product_status' => $item['product_status'] ?: 'active',
                'already_reviewed' => intval($item['already_reviewed']),
                'review' => null,
                'is_available' => !empty($item['product_name']) && $item['current_stock'] > 0
            ];

            if ($processed['already_reviewed']) {
                $processed['review'] = [
                    'rating' => intval($item['review_rating']),
                    'comment' => $item['review_comment'],
                    'date' => $item['review_date']
                ];
            }

            $summary['total_items']++;
            $summary['total_quantity'] += $processed['quantity'];
            $summary['subtotal'] += $processed['item_total'];

            if (!$processed['already_reviewed']) {
                $summary['reviewable_count']++;
            }

            $processedItems[] = $processed;
        }

        $summary['unique_products'] = count($processedItems);

        $shipping_cost = calculateShippingCost($summary['subtotal']);
        $grand_total = $summary['subtotal'] + $shipping_cost;

        echo json_encode([
            'success' => true,
            'order' => [
                'id' => intval($order['id']),
                'status' => $order['status'],
                'user_id' => intval($order['user_id']),
                'total_amount' => floatval($order['total_amount']),
                'user_confirmed' => isset($order['user_confirmed']) ? (bool)$order['user_confirmed'] : true
            ],
            'items' => $processedItems,
            'summary' => array_merge($summary, [
                'shipping_cost' => $shipping_cost,
                'grand_total' => $grand_total,
                'is_free_shipping' => $shipping_cost == 0,
                'shipping_threshold' => 100000
            ]),
            'permissions' => [
                'can_review' => !$is_admin && isset($order['user_confirmed']) && $order['user_confirmed'],
                'can_edit' => $is_admin,
                'is_admin' => $is_admin
            ]
        ]);
    } catch (Exception $e) {
        error_log("Error in getOrderItems: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getReviewableItems($conn, $order_id, $user_id)
{
    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'User authentication required']);
        return;
    }

    try {
        $stmt = $conn->prepare("
            SELECT id, status, COALESCE(user_confirmed, FALSE) as user_confirmed
            FROM orders 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            return;
        }

        if (!$order['user_confirmed']) {
            echo json_encode(['success' => false, 'error' => 'Order must be confirmed before reviewing']);
            return;
        }

        $stmt = $conn->prepare("
            SELECT 
                oi.product_id,
                oi.quantity,
                COALESCE(oi.price, oi.product_price, p.price) as unit_price,
                p.name,
                p.slug,
                p.rating as current_rating,
                p.review_count
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN reviews r ON r.product_id = p.id AND r.user_id = ? AND r.order_id = ?
            WHERE oi.order_id = ? AND r.id IS NULL
            ORDER BY oi.id
        ");
        $stmt->execute([$user_id, $order_id, $order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $reviewable_items = [];
        foreach ($items as $item) {
            $image_url = '';
            if (!empty($item['slug'])) {
                $image_url = $item['slug'] . '.jpg';
            } else if (!empty($item['name'])) {
                $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $item['name']));
                $image_url = $clean_name . '.jpg';
            }

            $reviewable_items[] = [
                'product_id' => intval($item['product_id']),
                'name' => $item['name'],
                'image_url' => $image_url,
                'quantity' => intval($item['quantity']),
                'unit_price' => floatval($item['unit_price']),
                'current_rating' => floatval($item['current_rating'] ?: 0),
                'review_count' => intval($item['review_count'] ?: 0)
            ];
        }

        echo json_encode([
            'success' => true,
            'items' => $reviewable_items,
            'reviewable_count' => count($reviewable_items),
            'order_status' => $order['status']
        ]);
    } catch (Exception $e) {
        error_log("Error in getReviewableItems: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getOrderItemsSummary($conn, $order_id, $user_id, $is_admin)
{
    try {
        $whereClause = $is_admin ? "oi.order_id = ?" : "oi.order_id = ? AND o.user_id = ?";
        $params = $is_admin ? [$order_id] : [$order_id, $user_id];

        $stmt = $conn->prepare("
            SELECT 
                COUNT(oi.id) as total_items,
                SUM(oi.quantity) as total_quantity,
                SUM(COALESCE(oi.price, oi.product_price, p.price) * oi.quantity) as subtotal,
                COUNT(DISTINCT oi.product_id) as unique_products,
                o.total_amount,
                o.status
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE $whereClause
            GROUP BY o.id, o.total_amount, o.status
        ");
        $stmt->execute($params);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$summary) {
            echo json_encode(['success' => false, 'error' => 'Order not found or no items']);
            return;
        }

        $subtotal = floatval($summary['subtotal']);
        $shipping_cost = calculateShippingCost($subtotal);

        echo json_encode([
            'success' => true,
            'summary' => [
                'total_items' => intval($summary['total_items']),
                'total_quantity' => intval($summary['total_quantity']),
                'unique_products' => intval($summary['unique_products']),
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping_cost,
                'grand_total' => $subtotal + $shipping_cost,
                'is_free_shipping' => $shipping_cost == 0,
                'order_total_amount' => floatval($summary['total_amount']),
                'order_status' => $summary['status']
            ]
        ]);
    } catch (Exception $e) {
        error_log("Error in getOrderItemsSummary: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function calculateShippingCost($totalAmount, $storedShippingCost = null)
{
    if ($storedShippingCost !== null) {
        return floatval($storedShippingCost);
    }

    return ($totalAmount >= 100000) ? 0 : 15000;
}

function validateOrderId($order_id)
{
    $order_id = intval($order_id);
    return ($order_id > 0) ? $order_id : false;
}

function formatCurrency($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
