<?php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;
$is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

if ($action === 'exportPDF') {
    if (!$user_id) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
        exit();
    }
} else {
    if (!$is_admin) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
        exit();
    }
}

include '../config/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'getAll':
            getAllOrders($conn);
            break;

        case 'get':
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
            getAllOrders($conn);
            break;
    }
} catch (Exception $e) {
    error_log("Orders API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function getAllOrders($conn)
{
    try {
        $query = "SELECT o.id, o.user_id, o.total_amount, o.shipping_cost, o.status, o.shipping_address, 
                         o.payment_method, o.notes, o.created_at, o.updated_at,
                         (o.total_amount + COALESCE(o.shipping_cost, 0)) as grand_total,
                         u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
                         CASE 
                             WHEN COUNT(DISTINCT oi.product_id) > 1 THEN 'Mixed Items'
                             WHEN COUNT(DISTINCT oi.product_id) = 1 THEN MAX(p.name)
                             ELSE 'No Items'
                         END as product_names,
                         COUNT(DISTINCT oi.product_id) as unique_product_count,
                         SUM(oi.quantity) as total_items
                  FROM orders o 
                  LEFT JOIN users u ON o.user_id = u.id 
                  LEFT JOIN order_items oi ON o.id = oi.order_id
                  LEFT JOIN products p ON oi.product_id = p.id
                  GROUP BY o.id
                  ORDER BY o.created_at DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$order) {
            $calculated_shipping = ($order['total_amount'] >= 100000) ? 0 : 15000;

            if ($order['shipping_cost'] === null) {
                $order['shipping_cost'] = $calculated_shipping;
                $order['grand_total'] = $order['total_amount'] + $calculated_shipping;
            }

            $order['total_amount'] = floatval($order['total_amount']);
            $order['shipping_cost'] = floatval($order['shipping_cost']);
            $order['grand_total'] = floatval($order['grand_total']);
            $order['unique_product_count'] = intval($order['unique_product_count']);
            $order['total_items'] = intval($order['total_items']);
        }

        echo json_encode($orders);
    } catch (Exception $e) {
        error_log("Error in getAllOrders: " . $e->getMessage());
        throw $e;
    }
}

function getOrderDetails($conn)
{
    $original_id = $_GET['id'] ?? '';

    $cleaned_id = str_replace('VG', '', $original_id);
    $cleaned_id = ltrim($cleaned_id, '0');
    $id = intval($cleaned_id);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid Order ID: ' . $original_id]);
        return;
    }

    try {
        $orderQuery = "SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
                       CASE 
                           WHEN o.shipping_cost IS NULL THEN 
                               CASE WHEN o.total_amount >= 100000 THEN 0 ELSE 15000 END
                           ELSE o.shipping_cost
                       END as calculated_shipping_cost
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ?";

        $orderStmt = $conn->prepare($orderQuery);
        $orderStmt->execute([$id]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Order not found with ID: ' . $id]);
            return;
        }

        $order['shipping_cost'] = floatval($order['calculated_shipping_cost']);
        $order['grand_total'] = floatval($order['total_amount']) + $order['shipping_cost'];

        try {
            $checkColumns = $conn->query("SHOW COLUMNS FROM order_items")->fetchAll(PDO::FETCH_COLUMN);
            $hasPriceField = in_array('price', $checkColumns);
            $hasProductPriceField = in_array('product_price', $checkColumns);
        } catch (Exception $e) {
            $hasPriceField = false;
            $hasProductPriceField = false;
        }

        $priceFieldSelection = '';
        if ($hasPriceField) {
            $priceFieldSelection = 'oi.price';
        } elseif ($hasProductPriceField) {
            $priceFieldSelection = 'oi.product_price';
        } else {
            $priceFieldSelection = 'p.price';
        }

        $itemsQuery = "SELECT oi.*, 
                              p.name as product_name, 
                              p.slug,
                              c.name as category_name,
                              $priceFieldSelection as unit_price,
                              ($priceFieldSelection * oi.quantity) as item_total
                       FROM order_items oi
                       LEFT JOIN products p ON oi.product_id = p.id
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE oi.order_id = ?
                       ORDER BY oi.id";

        $itemsStmt = $conn->prepare($itemsQuery);
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as &$item) {
            $unit_price = 0;
            if (isset($item['unit_price']) && $item['unit_price'] > 0) {
                $unit_price = floatval($item['unit_price']);
            } elseif ($hasPriceField && isset($item['price']) && $item['price'] > 0) {
                $unit_price = floatval($item['price']);
            } elseif ($hasProductPriceField && isset($item['product_price']) && $item['product_price'] > 0) {
                $unit_price = floatval($item['product_price']);
            }

            $item['unit_price'] = $unit_price;
            $item['quantity'] = intval($item['quantity']);
            $item['item_total'] = $unit_price * $item['quantity'];

            $item['category'] = $item['category_name'] ?: 'Uncategorized';

            if (!empty($item['slug'])) {
                $item['image_url'] = $item['slug'] . '.jpg';
            } else if (!empty($item['product_name'])) {
                $clean_name = strtolower(str_replace([' ', '&', ',', '.'], ['-', 'dan', '', ''], $item['product_name']));
                $clean_name = preg_replace('/[^a-z0-9\-]/', '', $clean_name);
                $clean_name = preg_replace('/-+/', '-', $clean_name);
                $clean_name = trim($clean_name, '-');
                $item['image_url'] = $clean_name . '.jpg';
            } else {
                $item['image_url'] = 'default-product.jpg';
            }
        }

        $history = [];

        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items,
            'history' => $history
        ]);
    } catch (Exception $e) {
        error_log("Error in getOrderDetails: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function exportOrderToPDF($conn)
{
    $order_id = $_GET['order_id'] ?? '';
    $user_id = $_SESSION['user_id'] ?? null;
    $is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';

    $cleaned_id = str_replace('VG', '', $order_id);
    $cleaned_id = ltrim($cleaned_id, '0');
    $id = intval($cleaned_id);

    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid Order ID']);
        return;
    }

    try {
        if (!$is_admin) {
            $authQuery = "SELECT COUNT(*) FROM orders WHERE id = ? AND user_id = ?";
            $authStmt = $conn->prepare($authQuery);
            $authStmt->execute([$id, $user_id]);
            if ($authStmt->fetchColumn() == 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Access denied']);
                return;
            }
        }

        $orderQuery = "SELECT o.*, u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
                       FROM orders o 
                       LEFT JOIN users u ON o.user_id = u.id 
                       WHERE o.id = ?";


        $orderStmt = $conn->prepare($orderQuery);
        $orderStmt->execute([$id]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Order not found']);
            return;
        }

        $itemsQuery = "SELECT oi.*, p.name as product_name, c.name as category_name,
                              COALESCE(oi.price, oi.product_price, p.price) as unit_price
                       FROM order_items oi
                       LEFT JOIN products p ON oi.product_id = p.id
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE oi.order_id = ?
                       ORDER BY oi.id";

        $itemsStmt = $conn->prepare($itemsQuery);
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $subtotal = floatval($order['total_amount']);
        $shipping_cost = ($subtotal >= 100000) ? 0 : 15000;
        $grand_total = $subtotal + $shipping_cost;

        $html = generateInvoiceHTML($order, $items, $subtotal, $shipping_cost, $grand_total);

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="invoice-VG' . str_pad($id, 6, '0', STR_PAD_LEFT) . '.html"');

        echo $html;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Error generating PDF: ' . $e->getMessage()]);
    }
}

function generateInvoiceHTML($order, $items, $subtotal, $shipping_cost, $grand_total)
{
    $order_id = 'VG' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
    $order_date = date('d M Y H:i', strtotime($order['created_at']));

    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice ' . $order_id . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #609966; padding-bottom: 20px; }
        .company-name { font-size: 28px; font-weight: bold; color: #609966; margin-bottom: 5px; }
        .company-info { font-size: 14px; color: #666; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .invoice-details, .customer-details { width: 48%; }
        .invoice-details h3, .customer-details h3 { color: #609966; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; color: #333; }
        .items-table th { background-color: #609966; color: white; }
        .total-row { background-color: #f8f9fa; font-weight: bold; }
        .grand-total { background-color: #609966; color: white; font-size: 16px; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #ffc107; color: #000; }
        .status-confirmed { background: #17a2b8; color: white; }
        .status-shipped { background: #fd7e14; color: white; }
        .status-delivered { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
        .print-btn { background: #609966; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()"> Print Invoice</button>
    
    <div class="header">
        <div class="company-name">VEGGIESTRY</div>
        <div class="company-info">Fresh Vegetables & Healthy Living<br>Email: veggiestry@gmail.com | Phone: (021) 1234-5678</div>
    </div>
    
    <div class="invoice-info">
        <div class="invoice-details">
            <h3>Invoice Details</h3>
            <table>
                <tr><td><strong>Invoice Number:</strong></td><td>' . $order_id . '</td></tr>
                <tr><td><strong>Date:</strong></td><td>' . $order_date . '</td></tr>
                <tr><td><strong>Status:</strong></td><td><span class="status-badge status-' . $order['status'] . '">' . strtoupper($order['status']) . '</span></td></tr>
                <tr><td><strong>Payment Method:</strong></td><td>' . ($order['payment_method'] ?: 'Bank Transfer') . '</td></tr>
            </table>
        </div>
        
        <div class="customer-details">
            <h3>Customer Details</h3>
            <table>
                <tr><td><strong>Name:</strong></td><td>' . ($order['customer_name'] ?: 'Guest Customer') . '</td></tr>
                <tr><td><strong>Email:</strong></td><td>' . ($order['customer_email'] ?: '-') . '</td></tr>
                <tr><td><strong>Phone:</strong></td><td>' . ($order['customer_phone'] ?: '-') . '</td></tr>
                <tr><td><strong>Address:</strong></td><td>' . ($order['shipping_address'] ?: '-') . '</td></tr>
            </table>
        </div>
    </div>
    
    <h3 style="color: #4CAF50; border-bottom: 2px solid #4CAF50; padding-bottom: 5px;">Order Items</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($items as $item) {
        $unit_price = floatval($item['unit_price']);
        $quantity = intval($item['quantity']);
        $item_total = $unit_price * $quantity;

        $html .= '<tr>
                <td><strong>' . ($item['product_name'] ?: 'Unknown Product') . '</strong></td>
                <td>' . ($item['category_name'] ?: 'Uncategorized') . '</td>
                <td>Rp ' . number_format($unit_price, 0, ',', '.') . '</td>
                <td>' . $quantity . '</td>
                <td>Rp ' . number_format($item_total, 0, ',', '.') . '</td>
            </tr>';
    }

    $html .= '</tbody>
    </table>
    
    <table style="width: 50%; margin-left: auto;">
        <tr class="total-row">
            <td><strong>Subtotal:</strong></td>
            <td><strong>Rp ' . number_format($subtotal, 0, ',', '.') . '</strong></td>
        </tr>
        <tr class="total-row">
            <td><strong>Shipping Cost:</strong></td>
            <td><strong>Rp ' . number_format($shipping_cost, 0, ',', '.') . '</strong></td>
        </tr>
        <tr class="grand-total">
            <td><strong>GRAND TOTAL:</strong></td>
            <td><strong>Rp ' . number_format($grand_total, 0, ',', '.') . '</strong></td>
        </tr>
    </table>';

    if ($order['notes']) {
        $html .= '<div style="margin-top: 30px;">
            <h3 style="color: #4CAF50;">Order Notes</h3>
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">' . nl2br(htmlspecialchars($order['notes'])) . '</div>
        </div>';
    }

    $html .= '<div class="footer">
        <p><strong>Thank you for your business!</strong></p>
        <p>This is a computer-generated invoice. For questions, please contact us at halo@veggiestry.com</p>
        <p>Generated on ' . date('d M Y H:i:s') . '</p>
    </div>
    
</body>
</html>';

    return $html;
}

function updateOrderStatus($conn)
{
    $id = intval($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Order ID tidak valid']);
        return;
    }

    $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'error' => 'Status tidak valid: ' . $status]);
        return;
    }

    try {
        $checkQuery = "SELECT id, status FROM orders WHERE id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$id]);
        $order = $checkStmt->fetch();

        if (!$order) {
            echo json_encode(['success' => false, 'error' => 'Pesanan tidak ditemukan']);
            return;
        }

        $currentStatus = $order['status'];
        if ($currentStatus === 'delivered' || $currentStatus === 'cancelled') {
            echo json_encode(['success' => false, 'error' => 'Pesanan yang sudah selesai/dibatalkan tidak dapat diubah']);
            return;
        }

        $conn->beginTransaction();

        try {
            try {
                $checkColumns = $conn->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
                $hasUpdatedAt = in_array('updated_at', $checkColumns);
            } catch (Exception $e) {
                $hasUpdatedAt = false;
            }

            if ($hasUpdatedAt) {
                $updateQuery = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
            } else {
                $updateQuery = "UPDATE orders SET status = ? WHERE id = ?";
            }

            $updateStmt = $conn->prepare($updateQuery);
            $updateResult = $updateStmt->execute([$status, $id]);

            if (!$updateResult) {
                throw new Exception('Gagal mengupdate status pesanan');
            }

            if ($status === 'cancelled' && $currentStatus !== 'cancelled') {
                restoreProductStock($conn, $id);
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Status pesanan berhasil diupdate']);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Transaction error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } catch (Exception $e) {
        error_log("Error in updateOrderStatus: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function restoreProductStock($conn, $orderId)
{
    try {
        $itemsQuery = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $itemsStmt = $conn->prepare($itemsQuery);
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();

        foreach ($items as $item) {
            try {
                $checkColumns = $conn->query("SHOW COLUMNS FROM products")->fetchAll(PDO::FETCH_COLUMN);
                if (in_array('sold', $checkColumns)) {
                    $updateStockQuery = "UPDATE products SET sold = GREATEST(0, sold - ?) WHERE id = ?";
                    $updateStockStmt = $conn->prepare($updateStockQuery);
                    $updateStockStmt->execute([$item['quantity'], $item['product_id']]);
                }
            } catch (Exception $e) {
                error_log("Error checking sold column: " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log("Error restoring product stock: " . $e->getMessage());
    }
}
