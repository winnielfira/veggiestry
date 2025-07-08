<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

requireLogin();

$user = getCurrentUser($conn);
$user_orders = getUserOrders($conn, $_SESSION['user_id']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);

    if (empty($full_name)) {
        $error = 'Nama lengkap harus diisi';
    } else {
        $stmt = $conn->prepare("
            UPDATE users 
            SET full_name = ?, phone = ?, address = ?, city = ?, postal_code = ?, updated_at = NOW() 
            WHERE id = ?
        ");

        if ($stmt->execute([$full_name, $phone, $address, $city, $postal_code, $_SESSION['user_id']])) {
            $success = 'Profil berhasil diperbarui';
            $user = getCurrentUser($conn);
        } else {
            $error = 'Gagal memperbarui profil';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua field password harus diisi';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Konfirmasi password baru tidak cocok';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password baru minimal 6 karakter';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = 'Password lama tidak benar';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");

        if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
            $success = 'Password berhasil diubah';
        } else {
            $error = 'Gagal mengubah password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Saya - Veggiestry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/account.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../assets/js/main.js"></script>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <main class="account-main">
        <div class="container">
            <div class="account-layout">
                <aside class="account-sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <h3><?= htmlspecialchars($user['full_name']) ?></h3>
                            <p><?= htmlspecialchars($user['email']) ?></p>
                        </div>
                    </div>

                    <nav class="account-nav">
                        <a href="javascript:void(0)" class="nav-item active" data-section="profile">
                            <i class="fas fa-user-edit"></i>
                            Profil Saya
                        </a>
                        <a href="javascript:void(0)" class="nav-item" data-section="orders">
                            <i class="fas fa-shopping-bag"></i>
                            Pesanan Saya
                        </a>
                        <a href="javascript:void(0)" class="nav-item" data-section="security">
                            <i class="fas fa-shield-alt"></i>
                            Keamanan
                        </a>
                        <a href="../auth/logout.php" class="nav-item logout" onclick="return confirm('Apakah Anda yakin ingin logout?')">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </nav>
                </aside>

                <div class="account-content">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div id="profile" class="section-content active">
                        <div class="content-header">
                            <h1>Profil Saya</h1>
                            <p>Kelola informasi profil Anda</p>
                        </div>

                        <form method="POST" class="profile-form">
                            <input type="hidden" name="update_profile" value="1">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name">Nama Lengkap</label>
                                    <input type="text" id="full_name" name="full_name" required
                                        value="<?= htmlspecialchars($user['full_name']) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                    <small>Email tidak dapat diubah</small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone">Nomor Telepon</label>
                                    <input type="tel" id="phone" name="phone"
                                        value="<?= htmlspecialchars($user['phone']) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="city">Kota</label>
                                    <input type="text" id="city" name="city"
                                        value="<?= htmlspecialchars($user['city']) ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="address">Alamat Lengkap</label>
                                <textarea id="address" name="address" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="postal_code">Kode Pos</label>
                                <input type="text" id="postal_code" name="postal_code"
                                    value="<?= htmlspecialchars($user['postal_code']) ?>">
                            </div>

                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i>
                                Simpan Perubahan
                            </button>
                        </form>
                    </div>

                    <div id="orders" class="section-content">
                        <div class="content-header">
                            <h1>Pesanan Saya</h1>
                            <p>Lihat riwayat pesanan Anda</p>
                        </div>

                        <?php if (empty($user_orders)): ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag"></i>
                                <h3>Belum ada pesanan</h3>
                                <p>Mulai berbelanja sekarang!</p>
                                <a href="../pages/products.php" class="btn-primary">Belanja Sekarang</a>
                            </div>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($user_orders as $order): ?>
                                    <?php
                                    $reviewStmt = $conn->prepare("
                                SELECT COUNT(*) as review_count 
                                FROM reviews 
                                WHERE user_id = ? AND order_id = ?
                            ");
                                    $reviewStmt->execute([$_SESSION['user_id'], $order['id']]);
                                    $reviewData = $reviewStmt->fetch();
                                    $hasReviewed = $reviewData['review_count'] > 0;

                                    $user_confirmed = isset($order['user_confirmed']) ? $order['user_confirmed'] : false;
                                    $status = $order['status'];
                                    ?>

                                    <div class="order-card" data-order-id="<?= $order['id'] ?>">
                                        <div class="order-header">
                                            <div class="order-info">
                                                <h3>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                                <p class="order-date"><?= date('d M Y', strtotime($order['created_at'])) ?></p>
                                            </div>
                                            <div class="order-status">
                                                <span class="status-badge status-<?= strtolower($status) ?>">
                                                    <?= strtoupper($status) ?>
                                                </span>
                                                <div class="order-total">
                                                    Rp <?= number_format($order['grand_total'], 0, ',', '.') ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="order-actions">
                                            <div class="left-actions">
                                                <?php if ($status === 'delivered' && !$user_confirmed): ?>
                                                    <button class="btn-complete" onclick="completeOrder(<?= $order['id'] ?>)">
                                                        <i class="fas fa-check-circle"></i> Pesanan Selesai
                                                    </button>
                                                <?php elseif ($status === 'delivered' && $user_confirmed && !$hasReviewed): ?>
                                                    <button class="btn-rate" onclick="openRatingModal(<?= $order['id'] ?>)">
                                                        <i class="fas fa-star"></i> Beri Rating
                                                    </button>
                                                <?php elseif ($status === 'delivered' && $user_confirmed && $hasReviewed): ?>
                                                    <button class="btn-completed" disabled>
                                                        <i class="fas fa-check-circle"></i> Selesai & Sudah Rating
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-waiting" disabled>
                                                        <i class="fas fa-clock"></i> <?= ucfirst($status) ?>
                                                    </button>
                                                <?php endif; ?>
                                            </div>

                                            <div class="right-actions">
                                                <button class="btn-invoice" onclick="printInvoice(<?= $order['id'] ?>)">
                                                    <i class="fas fa-file-invoice"></i> Print Invoice
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="security" class="section-content">
                        <div class="content-header">
                            <h1>Keamanan</h1>
                            <p>Kelola password dan pengaturan keamanan akun Anda</p>
                        </div>

                        <form method="POST" class="profile-form">
                            <input type="hidden" name="change_password" value="1">

                            <div class="form-group">
                                <label for="current_password">Password Lama</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="new_password">Password Baru</label>
                                    <input type="password" id="new_password" name="new_password" required minlength="6">
                                    <small>Minimal 6 karakter</small>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Konfirmasi Password Baru</label>
                                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                            </div>

                            <button type="submit" class="btn-primary">
                                <i class="fas fa-key"></i>
                                Ubah Password
                            </button>
                        </form>

                        <hr style="margin: 40px 0; border: none; border-top: 1px solid #e9ecef;">

                        <div class="security-info">
                            <h3>Informasi Keamanan</h3>
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="security-details">
                                    <h4>Password</h4>
                                    <p>Terakhir diubah: <?= $user['updated_at'] ? date('d M Y', strtotime($user['updated_at'])) : 'Belum pernah' ?></p>
                                </div>
                            </div>
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="security-details">
                                    <h4>Login Terakhir</h4>
                                    <p><?= $user['last_login'] ? date('d M Y H:i', strtotime($user['last_login'])) : 'Belum tersedia' ?></p>
                                </div>
                            </div>
                            <div class="security-item">
                                <div class="security-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <div class="security-details">
                                    <h4>Akun Dibuat</h4>
                                    <p><?= date('d M Y', strtotime($user['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="ratingModal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3><i class="fas fa-star"></i> Berikan Rating</h3>
                                <span class="close" onclick="closeRatingModal()">&times;</span>
                            </div>
                            <div class="modal-body">

                                <div id="loadingProducts" class="loading-container">
                                    <div class="loading-spinner">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <p>Memuat produk...</p>
                                    </div>
                                </div>

                                <div id="errorProducts" class="error-container" style="display: none;">
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <p id="errorMessage">Gagal memuat produk</p>
                                    </div>
                                </div>

                                <div id="ratingContainer" style="display: none;">
                                    <div class="products-preview">
                                        <h4>Produk yang akan diberi rating:</h4>
                                        <div id="productsList" class="products-list"></div>
                                    </div>

                                    <form id="simpleRatingForm">
                                        <input type="hidden" id="ratingOrderId" name="order_id">

                                        <div class="rating-section">
                                            <label>Berikan Rating untuk Semua Produk:</label>
                                            <div class="star-rating">
                                                <span class="star" data-rating="1">★</span>
                                                <span class="star" data-rating="2">★</span>
                                                <span class="star" data-rating="3">★</span>
                                                <span class="star" data-rating="4">★</span>
                                                <span class="star" data-rating="5">★</span>
                                            </div>
                                            <div class="rating-text">Pilih rating Anda</div>
                                            <input type="hidden" id="ratingValue" name="rating" required>
                                        </div>

                                        <div class="comment-section">
                                            <label for="ratingComment">Review untuk Semua Produk (Opsional):</label>
                                            <textarea
                                                id="ratingComment"
                                                name="comment"
                                                rows="3"
                                                placeholder="Tulis review umum untuk pesanan ini..."
                                                maxlength="300"></textarea>
                                        </div>

                                        <div class="rating-info">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Rating ini akan diterapkan untuk <strong id="productsCount">0</strong> produk dalam pesanan</span>
                                        </div>

                                        <div class="modal-actions">
                                            <button type="button" class="btn-secondary" onclick="closeRatingModal()">
                                                Batal
                                            </button>
                                            <button type="submit" class="btn-primary" id="submitRating" disabled>
                                                <i class="fas fa-paper-plane"></i> Kirim Rating
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
        function showSection(sectionName) {
            console.log('Switching to section:', sectionName);

            document.querySelectorAll('.section-content').forEach(section => {
                section.classList.remove('active');
            });

            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });

            const targetSection = document.getElementById(sectionName);
            if (targetSection) {
                targetSection.classList.add('active');
                console.log('Section shown:', sectionName);
            } else {
                console.error('Section not found:', sectionName);
            }

            const navItem = document.querySelector(`[data-section="${sectionName}"]`);
            if (navItem) {
                navItem.classList.add('active');
                console.log('Nav item activated:', sectionName);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing account navigation...');

            const navItems = document.querySelectorAll('.nav-item[data-section]');
            console.log('Found nav items:', navItems.length);

            navItems.forEach(item => {
                const section = item.getAttribute('data-section');
                console.log('Setting up nav item:', section);

                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Nav item clicked:', section);
                    showSection(section);
                });
            });

            setTimeout(() => {
                document.querySelectorAll('.alert').forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 5000);

            console.log('Account navigation initialized successfully');
        });

        async function completeOrder(orderId) {
            console.log('Complete order called for ID:', orderId);

            if (!confirm('Apakah Anda yakin pesanan sudah diterima dengan baik?')) {
                return;
            }

            const orderCard = document.querySelector(`[data-order-id="${orderId}"]`);
            if (orderCard) {
                const completeBtn = orderCard.querySelector('.btn-complete');
                if (completeBtn) {
                    completeBtn.disabled = true;
                    completeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                }
            }

            try {
                const response = await fetch('../api/confirm-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=confirm&order_id=${orderId}`
                });

                const result = await response.json();
                console.log('Confirm order result:', result);

                if (result.success) {
                    showNotification('Pesanan berhasil diselesaikan!', 'success');
                    updateOrderButton(orderId, 'rate');
                } else {
                    showNotification(result.error, 'error');
                    if (orderCard) {
                        const completeBtn = orderCard.querySelector('.btn-complete');
                        if (completeBtn) {
                            completeBtn.disabled = false;
                            completeBtn.innerHTML = '<i class="fas fa-check-circle"></i> Pesanan Selesai';
                        }
                    }
                }
            } catch (error) {
                console.error('Error completing order:', error);
                showNotification('Terjadi kesalahan: ' + error.message, 'error');
                if (orderCard) {
                    const completeBtn = orderCard.querySelector('.btn-complete');
                    if (completeBtn) {
                        completeBtn.disabled = false;
                        completeBtn.innerHTML = '<i class="fas fa-check-circle"></i> Pesanan Selesai';
                    }
                }
            }
        }

        let currentOrderId = null;
        let orderProducts = [];
        let selectedRating = 0;
        let isSubmitting = false;

        async function openRatingModal(orderId) {
            console.log('Open simple rating modal for order ID:', orderId);

            if (isSubmitting) {
                console.log('Already submitting, ignoring click');
                return;
            }

            if (!orderId || orderId === 'null' || orderId === null) {
                console.error('Invalid order ID provided:', orderId);
                showNotification('Order ID tidak valid', 'error');
                return;
            }

            currentOrderId = orderId;
            console.log('currentOrderId set to:', currentOrderId);

            resetModal();

            document.getElementById('ratingModal').style.display = 'block';
            document.getElementById('loadingProducts').style.display = 'block';
            document.getElementById('errorProducts').style.display = 'none';
            document.getElementById('ratingContainer').style.display = 'none';

            try {
                console.log('Fetching order items...');
                const response = await fetch(`../api/get-order-items.php?order_id=${orderId}&action=getReviewableItems`);
                const result = await response.json();

                console.log('Get reviewable items result:', result);

                if (result.success && result.items.length > 0) {
                    orderProducts = result.items;
                    setupRatingForm();
                } else if (result.success && result.items.length === 0) {
                    showError('Semua produk dalam pesanan ini sudah diberi rating');
                } else {
                    showError(result.error || 'Tidak ada produk yang dapat dinilai');
                }
            } catch (error) {
                console.error('Error loading order items:', error);
                showError('Terjadi kesalahan: ' + error.message);
            }
        }

        function setupRatingForm() {
            console.log('Setting up simple rating form for', orderProducts.length, 'products');

            document.getElementById('loadingProducts').style.display = 'none';
            document.getElementById('ratingContainer').style.display = 'block';

            document.getElementById('ratingOrderId').value = currentOrderId;

            displayProductsList();

            document.getElementById('productsCount').textContent = orderProducts.length;

            console.log('Simple rating form setup completed');
        }

        function displayProductsList() {
            const productsList = document.getElementById('productsList');
            productsList.innerHTML = '';

            orderProducts.forEach((product, index) => {
                const productItem = document.createElement('div');
                productItem.className = 'product-item';

                productItem.innerHTML = `
            <div class="product-preview">
                <div class="product-image">
                    <img src="../assets/images/${product.image_url}" 
                         alt="${product.name}" 
                         onerror="this.src='../assets/images/placeholder.jpg'">
                </div>
                <div class="product-info">
                    <span class="product-name">${product.name}</span>
                    <span class="product-details">Qty: ${product.quantity} | Rp ${formatCurrency(product.unit_price)}</span>
                </div>
            </div>
        `;

                productsList.appendChild(productItem);
            });
        }

        function resetModal() {

            orderProducts = [];
            selectedRating = 0;
            isSubmitting = false;

            document.getElementById('loadingProducts').style.display = 'none';
            document.getElementById('errorProducts').style.display = 'none';
            document.getElementById('ratingContainer').style.display = 'none';

            const ratingValueInput = document.getElementById('ratingValue');
            const ratingCommentInput = document.getElementById('ratingComment');

            if (ratingValueInput) ratingValueInput.value = '';
            if (ratingCommentInput) ratingCommentInput.value = '';

            document.querySelectorAll('#ratingModal .star').forEach(star => {
                star.classList.remove('active');
            });

            const ratingText = document.querySelector('#ratingModal .rating-text');
            if (ratingText) ratingText.textContent = 'Pilih rating Anda';

            updateSubmitButton();
        }

        function showError(message) {
            document.getElementById('loadingProducts').style.display = 'none';
            document.getElementById('ratingContainer').style.display = 'none';
            document.getElementById('errorProducts').style.display = 'block';
            document.getElementById('errorMessage').textContent = message;
        }

        function closeRatingModal() {
            console.log('🔄 Closing rating modal');

            if (isSubmitting) {
                if (!confirm('Rating sedang dikirim. Yakin ingin menutup?')) {
                    return;
                }
            }

            document.getElementById('ratingModal').style.display = 'none';

            currentOrderId = null;
            orderProducts = [];
            selectedRating = 0;
            isSubmitting = false;

            resetModal();
        }

        function updateSubmitButton() {
            const submitBtn = document.getElementById('submitRating');
            if (!submitBtn) return;

            const hasRating = selectedRating > 0;

            submitBtn.disabled = !hasRating || isSubmitting;

            if (hasRating && !isSubmitting) {
                submitBtn.innerHTML = `<i class="fas fa-paper-plane"></i> Kirim Rating ${selectedRating} Bintang`;
                submitBtn.classList.remove('btn-disabled');
            } else if (isSubmitting) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
            } else {
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Pilih Rating Dulu';
                submitBtn.classList.add('btn-disabled');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Setting up rating functionality...');

            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('star') &&
                    e.target.closest('#ratingModal') &&
                    !isSubmitting) {

                    selectedRating = parseInt(e.target.getAttribute('data-rating'));
                    const ratingValueInput = document.getElementById('ratingValue');
                    if (ratingValueInput) {
                        ratingValueInput.value = selectedRating;
                    }

                    console.log('Star clicked, rating:', selectedRating);

                    const modalStars = document.querySelectorAll('#ratingModal .star');
                    modalStars.forEach((s, i) => {
                        if (i < selectedRating) {
                            s.classList.add('active');
                        } else {
                            s.classList.remove('active');
                        }
                    });

                    const ratingTexts = {
                        1: 'Sangat Buruk',
                        2: 'Buruk',
                        3: 'Cukup',
                        4: 'Baik',
                        5: 'Sangat Baik'
                    };

                    const ratingTextEl = document.querySelector('#ratingModal .rating-text');
                    if (ratingTextEl) {
                        ratingTextEl.textContent = ratingTexts[selectedRating];
                        ratingTextEl.style.color = '#40513B';
                        ratingTextEl.style.fontWeight = '600';
                    }

                    updateSubmitButton();
                }
            });

            const simpleRatingForm = document.getElementById('simpleRatingForm');
            if (simpleRatingForm) {
                simpleRatingForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    console.log('========== SUBMIT SIMPLE RATING START ==========');
                    console.log('currentOrderId:', currentOrderId);

                    if (isSubmitting) {
                        console.log('Already submitting, preventing double submit');
                        return;
                    }

                    if (!currentOrderId) {
                        console.error('currentOrderId is null/undefined');
                        showNotification('Order ID tidak valid', 'error');
                        return;
                    }

                    if (selectedRating === 0) {
                        showNotification('Pilih rating terlebih dahulu', 'error');
                        return;
                    }

                    isSubmitting = true;
                    updateSubmitButton();

                    updateOrderButton(currentOrderId, 'submitting');

                    try {
                        console.log('Sending rating for all products...');
                        console.log('Rating:', selectedRating, 'Products:', orderProducts.length);

                        const formData = new FormData(this);
                        formData.append('action', 'submit_all');

                        const response = await fetch('../api/submit-review.php', {
                            method: 'POST',
                            body: formData
                        });

                        console.log('Submit rating response status:', response.status);

                        const responseText = await response.text();
                        console.log('Raw response text:', responseText);

                        let result;
                        try {
                            result = JSON.parse(responseText);
                        } catch (parseError) {
                            console.error('JSON parse error:', parseError);
                            throw new Error('Invalid JSON response from server');
                        }

                        console.log('Submit rating result:', result);

                        if (result.success) {
                            console.log('Rating submitted successfully for all products!');
                            showNotification(result.message, 'success');

                            updateOrderButton(currentOrderId, 'completed');

                            setTimeout(() => closeRatingModal(), 1000);

                        } else {
                            console.error('API returned error:', result.error);

                            if (result.error.includes('sudah diberikan') || result.error_code === 'REVIEWS_ALREADY_EXIST') {
                                showNotification('Rating sudah diberikan untuk pesanan ini', 'error');
                                updateOrderButton(currentOrderId, 'completed');
                                setTimeout(() => closeRatingModal(), 500);
                            } else {
                                showNotification(result.error, 'error');
                                updateOrderButton(currentOrderId, 'rate');
                            }
                        }

                    } catch (error) {
                        console.error('Error submitting rating:', error);
                        showNotification('Terjadi kesalahan: ' + error.message, 'error');
                        updateOrderButton(currentOrderId, 'rate');
                    } finally {
                        isSubmitting = false;
                        updateSubmitButton();
                        console.log('========== SUBMIT SIMPLE RATING END ==========');
                    }
                });
            }

            console.log('Rating functionality setup completed');
        });

        window.addEventListener('click', function(e) {
            const modal = document.getElementById('ratingModal');
            if (e.target === modal && !isSubmitting) {
                closeRatingModal();
            }
        });

        function updateOrderButton(orderId, state) {
            console.log('Updating button for order', orderId, 'to state:', state);

            const orderCard = document.querySelector(`[data-order-id="${orderId}"]`);
            if (!orderCard) {
                console.error('Order card not found for ID:', orderId);
                return false;
            }

            const leftActionsDiv = orderCard.querySelector('.left-actions');
            if (!leftActionsDiv) {
                console.error('Left actions div not found in order card');
                return false;
            }

            let buttonHTML = '';

            switch (state) {
                case 'rate':
                    buttonHTML = `
                <button class="btn-rate" onclick="openRatingModal(${orderId})">
                    <i class="fas fa-star"></i> Beri Rating
                </button>
            `;
                    break;
                case 'completed':
                    buttonHTML = `
                <button class="btn-completed" disabled>
                    <i class="fas fa-check-circle"></i> Selesai & Sudah Rating
                </button>
            `;
                    break;
                case 'submitting':
                    buttonHTML = `
                <button class="btn-submitting" disabled>
                    <i class="fas fa-spinner fa-spin"></i> Mengirim Rating...
                </button>
            `;
                    break;
                default:
                    console.error('Unknown button state:', state);
                    return false;
            }

            leftActionsDiv.innerHTML = buttonHTML;
            console.log('Button updated successfully to:', state);
            return true;
        }

        function showNotification(message, type = 'success') {
            console.log('Showing notification:', type, message);

            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notif => notif.remove());

            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
        <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
        <span>${message}</span>
    `;

            const bgColor = type === 'error' ? '#dc3545' : '#609966';

            notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: ${bgColor};
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        z-index: 10001;
        font-family: 'Poppins', sans-serif;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 350px;
    `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID').format(amount);
        }

        function formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID').format(amount);
        }

        function printInvoice(orderId) {
            console.log('Opening invoice for order:', orderId);

            const invoiceUrl = `../api/get-orders.php?action=exportPDF&order_id=${orderId}`;
            window.open(invoiceUrl, '_blank');
        }

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Account page: Force updating cart and wishlist counts...');

            setTimeout(function() {
                if (typeof updateCartCount === 'function') {
                    updateCartCount();
                    console.log('Cart count updated on account page');
                } else {
                    console.error('updateCartCount function not found! main.js not loaded?');
                }

                if (typeof updateWishlistCount === 'function') {
                    updateWishlistCount();
                    console.log('Wishlist count updated on account page');
                } else {
                    console.error('updateWishlistCount function not found! main.js not loaded?');
                }
            }, 1000);
        });

        console.log('Complete account.php JavaScript loaded successfully');
    </script>

    <style>
        :root {
            --light-cream: #EDF1D6;
            --soft-green: #9DC08B;
            --medium-green: #609966;
            --dark-green: #40513B;
            --white: #ffffff;
            --gray-light: #f8f9fa;
            --gray-medium: #6c757d;
            --gray-dark: #495057;
            --error-red: #dc3545;
            --success-green: #28a745;
            --warning-yellow: #ffc107;
        }

        .section-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .section-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-item.active {
            background-color: var(--medium-green) !important;
            color: var(--white) !important;
        }

        .nav-item.active i {
            color: var(--white) !important;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: var(--light-cream);
            color: var(--dark-green);
            border: 1px solid var(--soft-green);
        }

        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(64, 81, 59, 0.1);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 15px;
        }

        .order-card:hover {
            box-shadow: 0 4px 20px rgba(64, 81, 59, 0.15);
            transform: translateY(-2px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .order-info h3 {
            margin: 0 0 5px 0;
            color: var(--gray-dark);
            font-size: 18px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }

        .order-date {
            margin: 0;
            color: var(--gray-medium);
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }

        .order-status {
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 8px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background: var(--soft-green);
            color: var(--white);
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .order-total {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-dark);
            font-family: 'Poppins', sans-serif;
        }

        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-complete {
            padding: 10px 16px;
            background: var(--medium-green);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-complete:hover {
            background: var(--dark-green);
            transform: translateY(-1px);
        }

        .btn-rate {
            padding: 10px 16px;
            background: var(--soft-green);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-rate:hover {
            background: var(--medium-green);
            transform: translateY(-1px);
        }

        .btn-completed {
            padding: 10px 16px;
            background: var(--light-cream);
            color: var(--dark-green);
            border: 2px solid var(--soft-green);
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: not-allowed;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-cancelled {
            padding: 10px 16px;
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: not-allowed;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-waiting {
            padding: 10px 16px;
            background: #e9ecef;
            color: var(--gray-medium);
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: not-allowed;
            opacity: 0.7;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
        }

        .btn-submitting {
            padding: 10px 16px;
            background: var(--gray-medium);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: not-allowed;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
            opacity: 0.8;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--gray-light);
            border-radius: 12px;
            border: 2px dashed #dee2e6;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gray-medium);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: var(--gray-dark);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray-medium);
            font-family: 'Poppins', sans-serif;
            margin-bottom: 25px;
        }

        .security-info {
            margin-top: 30px;
        }

        .security-info h3 {
            color: var(--dark-green);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: var(--light-cream);
            border-radius: 10px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .security-item:hover {
            background: var(--soft-green);
            color: var(--white);
        }

        .security-icon {
            width: 50px;
            height: 50px;
            background: var(--medium-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 20px;
        }

        .security-item:hover .security-icon {
            background: var(--white);
            color: var(--medium-green);
        }

        .security-details h4 {
            margin: 0 0 5px 0;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 16px;
            color: var(--dark-green);
        }

        .security-item:hover .security-details h4 {
            color: var(--white);
        }

        .security-details p {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            color: var(--gray-medium);
        }

        .security-item:hover .security-details p {
            color: rgba(255, 255, 255, 0.9);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-green);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--soft-green);
            box-shadow: 0 0 0 3px rgba(157, 192, 139, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: var(--gray-medium);
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            padding: 12px 24px;
            background: var(--medium-green);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: var(--dark-green);
            transform: translateY(-1px);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
        }

        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 550px;
            max-height: 90vh;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.3s ease;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .modal-body {
            padding: 30px 25px;
            overflow-y: auto;
            flex: 1;
            max-height: calc(90vh - 120px);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.9);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            background: var(--medium-green);
            color: var(--white);
        }

        .modal-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 18px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: var(--white);
            opacity: 0.8;
            transition: opacity 0.3s;
            line-height: 1;
        }

        .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 30px 25px;
        }

        .loading-container {
            text-align: center;
            padding: 40px 20px;
        }

        .loading-spinner i {
            font-size: 32px;
            color: var(--medium-green);
            margin-bottom: 15px;
        }

        .loading-spinner p {
            color: var(--gray-dark);
            font-family: 'Poppins', sans-serif;
            margin: 0;
        }

        .error-container {
            text-align: center;
            padding: 40px 20px;
        }

        .error-message i {
            font-size: 32px;
            color: var(--error-red);
            margin-bottom: 15px;
        }

        .error-message p {
            color: var(--error-red);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            font-weight: 500;
        }

        .products-preview {
            margin-bottom: 25px;
        }

        .products-preview h4 {
            margin: 0 0 15px 0;
            color: var(--dark-green);
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 16px;
        }

        .products-list {
            background: var(--gray-light);
            border-radius: 10px;
            padding: 15px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
        }

        .product-item {
            margin-bottom: 12px;
        }

        .product-item:last-child {
            margin-bottom: 0;
        }

        .product-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            background: var(--white);
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .product-image {
            flex-shrink: 0;
        }

        .product-image img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .product-name {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--dark-green);
            font-size: 14px;
        }

        .product-details {
            font-family: 'Poppins', sans-serif;
            color: var(--gray-medium);
            font-size: 12px;
        }

        .rating-section {
            margin-bottom: 25px;
            text-align: center;
        }

        .rating-section label {
            display: block;
            margin-bottom: 20px;
            font-weight: 600;
            color: var(--dark-green);
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
        }

        .star-rating {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .star {
            font-size: 40px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }

        .star:hover {
            transform: scale(1.1);
            color: #ffd700;
        }

        .star.active {
            color: #ffd700;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .rating-text {
            font-size: 16px;
            font-weight: 500;
            color: var(--dark-green);
            min-height: 24px;
            font-family: 'Poppins', sans-serif;
        }

        .comment-section {
            margin-bottom: 20px;
        }

        .comment-section label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark-green);
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
        }

        .comment-section textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
            min-height: 80px;
        }

        .comment-section textarea:focus {
            outline: none;
            border-color: var(--soft-green);
            box-shadow: 0 0 0 3px rgba(157, 192, 139, 0.1);
        }

        .rating-info {
            background: var(--light-cream);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--soft-green);
        }

        .rating-info i {
            color: var(--medium-green);
            font-size: 18px;
        }

        .rating-info span {
            font-family: 'Poppins', sans-serif;
            color: var(--dark-green);
            font-size: 14px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-secondary {
            padding: 12px 24px;
            background: #e9ecef;
            color: var(--gray-dark);
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn-secondary:hover {
            background: #dee2e6;
            transform: translateY(-1px);
        }

        .modal .btn-primary {
            padding: 12px 24px;
            background: var(--medium-green);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal .btn-primary:hover:not(:disabled) {
            background: var(--dark-green);
            transform: translateY(-1px);
        }

        .modal .btn-primary:disabled {
            background: var(--gray-medium);
            cursor: not-allowed;
            transform: none;
        }

        .modal .btn-primary.btn-disabled {
            background: var(--gray-medium);
            cursor: not-allowed;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .security-item {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .security-details {
                text-align: center;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .order-status {
                text-align: left;
            }

            .order-actions {
                width: 100%;
            }

            .btn-complete,
            .btn-rate,
            .btn-completed,
            .btn-waiting,
            .btn-cancelled {
                flex: 1;
                justify-content: center;
                font-size: 12px;
                padding: 8px 12px;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .modal-body {
                padding: 25px 20px;
            }

            .star {
                font-size: 36px;
            }

            .rating-section label {
                font-size: 16px;
            }

            .modal-actions {
                flex-direction: column;
            }

            .btn-secondary,
            .modal .btn-primary {
                width: 100%;
                justify-content: center;
            }

            .product-preview {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }

            .products-list {
                max-height: 150px;
            }
        }

        @media (max-width: 480px) {
            .order-card {
                padding: 15px;
            }

            .order-total {
                font-size: 16px;
            }

            .star {
                font-size: 32px;
                gap: 6px;
            }

            .modal-header {
                padding: 15px 20px;
            }

            .modal-header h3 {
                font-size: 16px;
            }

            .product-image img {
                width: 40px;
                height: 40px;
            }

            .rating-info {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
        }

        .order-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .left-actions {
            display: flex;
            gap: 10px;
            flex: 1;
        }

        .right-actions {
            display: flex;
            gap: 10px;
        }

        .btn-invoice {
            padding: 10px 16px;
            background: rgb(255, 0, 0);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            font-family: 'Poppins', sans-serif;
            white-space: nowrap;
        }

        .btn-invoice:hover {
            background: rgb(255, 47, 0);
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .order-actions {
                flex-direction: column;
                gap: 10px;
            }

            .left-actions,
            .right-actions {
                width: 100%;
                justify-content: center;
            }

            .btn-invoice {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</body>

</html>