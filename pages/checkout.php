<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getCurrentUser($conn);
$cart_items = getCartItems($conn, $user_id);

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

$cart_total = getCartTotal($conn, $user_id);
$shipping_cost = $cart_total >= 100000 ? 0 : 15000;
$final_total = $cart_total + $shipping_cost;

$error = '';

foreach ($cart_items as $item) {
    $stmt = $conn->prepare("
        SELECT stock, sold, name, 
               (stock - COALESCE(sold, 0)) as available_stock 
        FROM products 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->execute([$item['product_id']]);
    $product_check = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product_check) {
        $_SESSION['error'] = "Produk {$item['name']} tidak tersedia lagi";
        header('Location: cart.php');
        exit();
    }

    $available_stock = max(0, intval($product_check['available_stock']));

    if ($item['quantity'] > $available_stock) {
        $_SESSION['error'] = "Stok {$product_check['name']} tidak mencukupi. Stok tersedia: {$available_stock}, diminta: {$item['quantity']}";
        header('Location: cart.php');
        exit();
    }
}

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $shipping_city = trim($_POST['shipping_city'] ?? '');
    $shipping_postal_code = trim($_POST['shipping_postal_code'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (isset($_POST['address_type']) && $_POST['address_type'] === 'existing') {
        $shipping_address = $user['address'];
        $shipping_city = $user['city'];
        $shipping_postal_code = $user['postal_code'];
    }

    if (empty($shipping_address) || empty($shipping_city) || empty($payment_method)) {
        $error = 'Alamat pengiriman dan metode pembayaran harus diisi';

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $error
            ]);
            exit;
        }
    } else {
        try {
            foreach ($cart_items as $item) {
                $stmt = $conn->prepare("
                    SELECT stock, sold, name,
                           (stock - COALESCE(sold, 0)) as available_stock 
                    FROM products 
                    WHERE id = ? AND status = 'active'
                ");
                $stmt->execute([$item['product_id']]);
                $product_data = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product_data) {
                    throw new Exception("Produk tidak ditemukan: {$item['name']}");
                }

                $available_stock = max(0, intval($product_data['available_stock']));

                if ($item['quantity'] > $available_stock) {
                    throw new Exception("Stok tidak mencukupi untuk {$product_data['name']}. Tersedia: {$available_stock}, diminta: {$item['quantity']}");
                }
            }

            $order_id = createOrderWithStockUpdate($conn, $user_id, $cart_items, $shipping_address, $shipping_city, $shipping_postal_code, $payment_method, $notes);


            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'order_id' => $order_id,
                    'redirect' => 'order-success.php?order_id=' . $order_id
                ]);
                exit;
            } else {
                header('Location: order-success.php?order_id=' . $order_id);
                exit;
            }
        } catch (Exception $e) {

            $error = 'Gagal membuat pesanan: ' . $e->getMessage();
            error_log("Checkout error: " . $e->getMessage());

            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $error
                ]);
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Veggiestry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/checkout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <main class="checkout-main">
        <div class="container">
            <div class="checkout-header">
                <h1>Checkout</h1>
                <div class="checkout-steps">
                    <div class="step active">
                        <span class="step-number">1</span>
                        <span class="step-text">Keranjang</span>
                    </div>
                    <div class="step active">
                        <span class="step-number">2</span>
                        <span class="step-text">Checkout</span>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <span class="step-text">Selesai</span>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="checkout-form" id="checkoutForm">
                <div class="checkout-layout">
                    <div class="checkout-details">
                        <div class="checkout-section">
                            <h2>
                                <i class="fas fa-map-marker-alt"></i>
                                Alamat Pengiriman
                            </h2>

                            <div class="address-options">
                                <label class="address-option">
                                    <input type="radio" name="address_type" value="existing" checked onchange="toggleAddressForm()">
                                    <div class="address-content">
                                        <h4>Alamat Utama</h4>
                                        <p><?= htmlspecialchars($user['full_name']) ?></p>
                                        <p><?= htmlspecialchars($user['phone']) ?></p>
                                        <p><?= htmlspecialchars($user['address']) ?></p>
                                        <p><?= htmlspecialchars($user['city']) ?> <?= htmlspecialchars($user['postal_code']) ?></p>
                                    </div>
                                </label>

                                <label class="address-option">
                                    <input type="radio" name="address_type" value="new" onchange="toggleAddressForm()">
                                    <div class="address-content">
                                        <h4>Alamat Baru</h4>
                                        <p>Gunakan alamat pengiriman yang berbeda</p>
                                    </div>
                                </label>
                            </div>

                            <div id="new-address-form" class="new-address-form" style="display: none;">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="shipping_address">Alamat Lengkap</label>
                                        <textarea id="shipping_address" name="shipping_address" rows="3" placeholder="Masukkan alamat lengkap"></textarea>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="shipping_city">Kota</label>
                                        <input type="text" id="shipping_city" name="shipping_city" placeholder="Nama kota">
                                    </div>
                                    <div class="form-group">
                                        <label for="shipping_postal_code">Kode Pos</label>
                                        <input type="text" id="shipping_postal_code" name="shipping_postal_code" placeholder="Kode pos">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="checkout-section">
                            <h2>
                                <i class="fas fa-credit-card"></i>
                                Metode Pembayaran
                            </h2>

                            <div class="payment-methods">
                                <label class="payment-method">
                                    <input type="radio" name="payment_method" value="bank_transfer" required>
                                    <div class="payment-content">
                                        <div class="payment-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="payment-info">
                                            <h4>Transfer Bank</h4>
                                            <p>BCA, Mandiri, BNI, BRI</p>
                                        </div>
                                    </div>
                                </label>

                                <label class="payment-method">
                                    <input type="radio" name="payment_method" value="e_wallet" required>
                                    <div class="payment-content">
                                        <div class="payment-icon">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <div class="payment-info">
                                            <h4>E-Wallet</h4>
                                            <p>GoPay, OVO, DANA, LinkAja</p>
                                        </div>
                                    </div>
                                </label>

                                <label class="payment-method">
                                    <input type="radio" name="payment_method" value="credit_card" required>
                                    <div class="payment-content">
                                        <div class="payment-icon">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="payment-info">
                                            <h4>Kartu Kredit</h4>
                                            <p>Visa, Mastercard, JCB</p>
                                        </div>
                                    </div>
                                </label>

                                <label class="payment-method">
                                    <input type="radio" name="payment_method" value="cod" required>
                                    <div class="payment-content">
                                        <div class="payment-icon">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                        <div class="payment-info">
                                            <h4>Bayar di Tempat (COD)</h4>
                                            <p>Bayar saat barang diterima</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="checkout-section">
                            <h2>
                                <i class="fas fa-sticky-note"></i>
                                Catatan Pesanan
                            </h2>

                            <div class="form-group">
                                <textarea name="notes" rows="3" placeholder="Catatan untuk penjual (opsional)"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="order-summary">
                        <div class="summary-card">
                            <h3>Ringkasan Pesanan</h3>

                            <div class="order-items">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="order-item">
                                        <img src="../assets/images/<?= $item['image'] ?>" alt="<?= $item['name'] ?>">
                                        <div class="item-details">
                                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                                            <p><?= $item['quantity'] ?>x Rp <?= number_format($item['price'], 0, ',', '.') ?></p>
                                        </div>
                                        <div class="item-total">
                                            Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="summary-calculations">
                                <div class="summary-row">
                                    <span>Subtotal (<?= count($cart_items) ?> item)</span>
                                    <span>Rp <?= number_format($cart_total, 0, ',', '.') ?></span>
                                </div>

                                <div class="summary-row">
                                    <span>Ongkos Kirim</span>
                                    <span>
                                        <?php if ($shipping_cost > 0): ?>
                                            Rp <?= number_format($shipping_cost, 0, ',', '.') ?>
                                        <?php else: ?>
                                            <span class="free-shipping">GRATIS</span>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <?php if ($cart_total < 100000): ?>
                                    <div class="free-shipping-info">
                                        <i class="fas fa-info-circle"></i>
                                        Belanja Rp <?= number_format(100000 - $cart_total, 0, ',', '.') ?> lagi untuk gratis ongkir!
                                    </div>
                                <?php endif; ?>

                                <div class="summary-divider"></div>

                                <div class="summary-total">
                                    <span>Total Pembayaran</span>
                                    <span>Rp <?= number_format($final_total, 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <button type="submit" class="btn-place-order" id="submitOrder">
                                <i class="fas fa-lock"></i>
                                Buat Pesanan
                            </button>

                            <div class="security-info">
                                <i class="fas fa-shield-alt"></i>
                                <span>Transaksi Anda aman dan terenkripsi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/checkout.js"></script>
    <script>
        function toggleAddressForm() {
            const addressType = document.querySelector('input[name="address_type"]:checked').value;
            const newAddressForm = document.getElementById('new-address-form');

            if (addressType === 'new') {
                newAddressForm.style.display = 'block';
                document.getElementById('shipping_address').required = true;
                document.getElementById('shipping_city').required = true;
                document.getElementById('shipping_postal_code').required = true;
            } else {
                newAddressForm.style.display = 'none';
                document.getElementById('shipping_address').required = false;
                document.getElementById('shipping_city').required = false;
                document.getElementById('shipping_postal_code').required = false;
            }
        }
    </script>


</body>

</html>