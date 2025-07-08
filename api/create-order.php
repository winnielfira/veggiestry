<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../config/database.php';

try {
    $conn->beginTransaction();

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT c.product_id, c.quantity, p.name, p.price, p.stock, p.sold,
               (p.stock - COALESCE(p.sold, 0)) as available_stock
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND p.status = 'active'
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        throw new Exception('Keranjang kosong');
    }

    foreach ($cart_items as $item) {
        $available_stock = max(0, intval($item['available_stock']));
        if ($item['quantity'] > $available_stock) {
            throw new Exception("Stok tidak mencukupi untuk {$item['name']}. Tersedia: {$available_stock}, diminta: {$item['quantity']}");
        }
    }

    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    $shipping_cost = ($subtotal >= 100000) ? 0 : 15000;
    $total = $subtotal + $shipping_cost;

    $shipping_address = $_POST['shipping_address'] ?? '';
    $shipping_city = $_POST['shipping_city'] ?? '';
    $shipping_postal_code = $_POST['shipping_postal_code'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $notes = $_POST['notes'] ?? '';

    $order_number = 'VEG-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, order_number, total_amount, shipping_cost, 
                          shipping_address, shipping_city, shipping_postal_code, 
                          payment_method, notes, status, payment_status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
    ");
    $stmt->execute([
        $user_id,
        $order_number,
        $subtotal,
        $shipping_cost,
        $shipping_address,
        $shipping_city,
        $shipping_postal_code,
        $payment_method,
        $notes
    ]);

    $order_id = $conn->lastInsertId();

    $stmt_order_item = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt_update_stock = $conn->prepare("
        UPDATE products 
        SET sold = sold + ?, updated_at = NOW() 
        WHERE id = ?
    ");

    foreach ($cart_items as $item) {
        $stmt_order_item->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        ]);

        $stmt_update_stock->execute([
            $item['quantity'],
            $item['product_id']
        ]);
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order berhasil dibuat',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'total' => $total
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Create order error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
