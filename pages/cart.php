<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_quantity':
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 1);

            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']);
                exit;
            }

            try {
                $stmt = $conn->prepare("
                    SELECT stock, COALESCE(sold, 0) as sold, name,
                           (stock - COALESCE(sold, 0)) as available_stock 
                    FROM products 
                    WHERE id = ? AND status = 'active'
                ");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
                    exit;
                }

                $available_stock = max(0, intval($product['available_stock']));

                if ($quantity <= 0) {
                    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                    $result = $stmt->execute([$user_id, $product_id]);
                    echo json_encode([
                        'success' => $result,
                        'message' => $result ? 'Produk dihapus dari keranjang' : 'Gagal menghapus produk'
                    ]);
                    exit;
                }

                if ($quantity > $available_stock) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Stok tidak mencukupi! Maksimal {$available_stock} item tersedia",
                        'available_stock' => $available_stock
                    ]);
                    exit;
                }

                $success = updateCartQuantity($conn, $user_id, $product_id, $quantity);

                if ($success) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Jumlah berhasil diupdate',
                        'available_stock' => $available_stock
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate jumlah']);
                }
            } catch (Exception $e) {
                error_log("Error updating cart quantity: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
            }
            exit;

        case 'remove_item':
            $product_id = intval($_POST['product_id'] ?? 0);

            if ($product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Product ID tidak valid']);
                exit;
            }

            $success = removeFromCart($conn, $user_id, $product_id);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Produk berhasil dihapus' : 'Gagal menghapus produk'
            ]);
            exit;

        case 'clear_cart':
            $success = clearCart($conn, $user_id);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Keranjang berhasil dikosongkan' : 'Gagal mengosongkan keranjang'
            ]);
            exit;
    }
}

function getCartItemsWithStock($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.stock, p.slug, COALESCE(p.sold, 0) as sold,
               (p.stock - COALESCE(p.sold, 0)) as available_stock
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND p.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();

    foreach ($items as &$item) {
        if (!empty($item['slug'])) {
            $item['image'] = $item['slug'] . '.jpg';
        } else {
            $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $item['name']));
            $item['image'] = $clean_name . '.jpg';
        }

        $item['available_stock'] = max(0, intval($item['available_stock']));
        $item['is_out_of_stock'] = ($item['available_stock'] <= 0);
        $item['is_low_stock'] = ($item['available_stock'] > 0 && $item['available_stock'] <= 5);
        $item['exceeds_stock'] = ($item['quantity'] > $item['available_stock']);

        if ($item['exceeds_stock'] && $item['available_stock'] > 0) {
            $corrected_quantity = $item['available_stock'];
            updateCartQuantity($conn, $user_id, $item['product_id'], $corrected_quantity);
            $item['quantity'] = $corrected_quantity;
            $item['exceeds_stock'] = false;
        }
    }

    return $items;
}

$cart_items = getCartItemsWithStock($conn, $user_id);
$cart_total = getCartTotal($conn, $user_id);
$shipping_cost = $cart_total >= 100000 ? 0 : 15000;
$final_total = $cart_total + $shipping_cost;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Veggiestry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/cart.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .stock-status {
            margin: 5px 0;
        }

        .stock-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .stock-badge.in-stock {
            background-color: #d4edda;
            color: #155724;
        }

        .stock-badge.low-stock {
            background-color: #fff3cd;
            color: #856404;
        }

        .stock-badge.out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }

        .quantity-controls button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .quantity-controls input:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <main class="cart-main">
        <div class="container">
            <div class="cart-header">
                <h1>Keranjang Belanja</h1>
                <p><?= count($cart_items) ?> item dalam keranjang</p>
            </div>

            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2>Keranjang Anda Kosong</h2>
                    <p>Belum ada produk yang ditambahkan ke keranjang</p>
                    <a href="products.php" class="btn-primary">Mulai Belanja</a>
                </div>
            <?php else: ?>

                <div class="cart-layout">
                    <div class="cart-items">
                        <div class="cart-items-header">
                            <h2>Produk</h2>
                            <button class="clear-cart-btn" onclick="clearCart()">
                                <i class="fas fa-trash"></i>
                                Kosongkan Keranjang
                            </button>
                        </div>

                        <div class="cart-items-list">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item" data-product-id="<?= $item['product_id'] ?>">
                                    <div class="item-image">
                                        <img src="../assets/images/<?= $item['image'] ?>" alt="<?= $item['name'] ?>">
                                    </div>
                                    <div class="item-details">
                                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                                        <p class="item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></p>

                                        <div class="stock-status">
                                            <?php if ($item['is_out_of_stock']): ?>
                                                <span class="stock-badge out-of-stock">Stok Habis</span>
                                            <?php elseif ($item['is_low_stock']): ?>
                                                <span class="stock-badge low-stock">Stok Terbatas (<?= $item['available_stock'] ?> tersisa)</span>
                                            <?php else: ?>
                                                <span class="stock-badge in-stock">Stok Tersedia (<?= $item['available_stock'] ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="item-quantity">
                                        <div class="quantity-controls">
                                            <button onclick="updateQuantity(<?= $item['product_id'] ?>, <?= max(1, $item['quantity'] - 1) ?>)"
                                                <?= $item['quantity'] <= 1 || $item['is_out_of_stock'] ? 'disabled' : '' ?>>-</button>
                                            <input type="number"
                                                value="<?= $item['quantity'] ?>"
                                                min="1"
                                                max="<?= $item['available_stock'] ?>"
                                                onchange="updateQuantity(<?= $item['product_id'] ?>, this.value)"
                                                <?= $item['is_out_of_stock'] ? 'disabled' : '' ?>>
                                            <button onclick="updateQuantity(<?= $item['product_id'] ?>, <?= min($item['available_stock'], $item['quantity'] + 1) ?>)"
                                                <?= $item['quantity'] >= $item['available_stock'] || $item['is_out_of_stock'] ? 'disabled' : '' ?>>+</button>
                                        </div>
                                    </div>
                                    <div class="item-total">
                                        <p class="total-price">Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></p>
                                        <button class="remove-btn" onclick="removeItem(<?= $item['product_id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="cart-summary">
                        <div class="summary-card">
                            <h3>Ringkasan Pesanan</h3>

                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span id="subtotal">Rp <?= number_format($cart_total, 0, ',', '.') ?></span>
                            </div>

                            <div class="summary-row">
                                <span>Ongkos Kirim</span>
                                <span id="shipping-cost">
                                    <?php if ($shipping_cost > 0): ?>
                                        Rp <?= number_format($shipping_cost, 0, ',', '.') ?>
                                    <?php else: ?>
                                        <span class="free-shipping">GRATIS</span>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <?php if ($cart_total < 100000 && $cart_total > 0): ?>
                                <div class="free-shipping-info">
                                    <i class="fas fa-info-circle"></i>
                                    Belanja Rp <?= number_format(100000 - $cart_total, 0, ',', '.') ?> lagi untuk gratis ongkir!
                                </div>
                            <?php endif; ?>

                            <div class="summary-divider"></div>

                            <div class="summary-total">
                                <span>Total</span>
                                <span id="final-total">Rp <?= number_format($final_total, 0, ',', '.') ?></span>
                            </div>

                            <button class="checkout-btn" onclick="proceedToCheckout()">
                                <i class="fas fa-credit-card"></i>
                                Lanjut ke Pembayaran
                            </button>

                            <div class="continue-shopping">
                                <a href="products.php">
                                    <i class="fas fa-arrow-left"></i>
                                    Lanjut Belanja
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/cart.js"></script>
</body>

</html>