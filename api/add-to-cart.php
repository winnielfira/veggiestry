<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu']);
    exit();
}

include '../config/database.php';

$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$user_id = $_SESSION['user_id'];

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak valid']);
    exit();
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Jumlah harus lebih dari 0']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT stock, sold, name, 
               (stock - COALESCE(sold, 0)) as available_stock 
        FROM products 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan atau tidak aktif']);
        exit();
    }

    $available_stock = max(0, intval($product['available_stock']));

    if ($available_stock <= 0) {
        echo json_encode([
            'success' => false,
            'message' => "Maaf, produk {$product['name']} sedang habis",
            'available_stock' => 0
        ]);
        exit();
    }

    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as cart_quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $cart_data = $stmt->fetch(PDO::FETCH_ASSOC);
    $existing_cart_quantity = intval($cart_data['cart_quantity']);

    $total_requested = $existing_cart_quantity + $quantity;

    if ($total_requested > $available_stock) {
        $available_to_add = max(0, $available_stock - $existing_cart_quantity);

        if ($available_to_add <= 0) {
            echo json_encode([
                'success' => false,
                'message' => "Produk {$product['name']} sudah mencapai batas maksimal di keranjang Anda (stok tersedia: {$available_stock})",
                'available_stock' => $available_stock,
                'in_cart' => $existing_cart_quantity
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "Stok tidak mencukupi. Stok tersedia: {$available_stock}, di keranjang: {$existing_cart_quantity}, maksimal bisa ditambah: {$available_to_add}",
                'available_stock' => $available_stock,
                'in_cart' => $existing_cart_quantity,
                'can_add' => $available_to_add
            ]);
        }
        exit();
    }

    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_item) {
        $new_quantity = $existing_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_quantity, $existing_item['id']]);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }

    echo json_encode([
        'success' => true,
        'message' => "{$quantity} produk berhasil ditambahkan ke keranjang",
        'available_stock' => $available_stock,
        'total_in_cart' => $total_requested
    ]);
} catch (Exception $e) {
    error_log("Error adding to cart: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
