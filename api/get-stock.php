<?php
session_start();
header('Content-Type: application/json');

$product_id = intval($_GET['product_id'] ?? $_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']);
    exit();
}

include '../config/database.php';

try {
    $stmt = $conn->prepare("
        SELECT 
            id,
            name,
            stock,
            COALESCE(sold, 0) as sold,
            (stock - COALESCE(sold, 0)) as available_stock,
            status
        FROM products 
        WHERE id = ? AND status = 'active'
    ");

    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode([
            'success' => false,
            'message' => 'Produk tidak ditemukan atau tidak aktif'
        ]);
        exit();
    }

    $available_stock = max(0, intval($product['available_stock']));
    $is_out_of_stock = ($available_stock <= 0);
    $is_low_stock = ($available_stock > 0 && $available_stock <= 5);

    $cart_quantity = 0;
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(quantity), 0) as cart_quantity 
            FROM cart 
            WHERE user_id = ? AND product_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $cart_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $cart_quantity = intval($cart_data['cart_quantity']);
    }

    $can_add = max(0, $available_stock - $cart_quantity);

    echo json_encode([
        'success' => true,
        'product_id' => $product['id'],
        'product_name' => $product['name'],
        'original_stock' => intval($product['stock']),
        'sold' => intval($product['sold']),
        'available_stock' => $available_stock,
        'in_cart' => $cart_quantity,
        'can_add' => $can_add,
        'is_out_of_stock' => $is_out_of_stock,
        'is_low_stock' => $is_low_stock,
        'stock_status' => $is_out_of_stock ? 'out_of_stock' : ($is_low_stock ? 'low_stock' : 'in_stock'),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Error getting stock: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem'
    ]);
}
