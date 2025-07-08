<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist Saya - Veggiestry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/products.css">
    <link rel="stylesheet" href="../assets/css/wishlist.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <main class="wishlist-main">
        <div class="container">
            <div class="wishlist-header">
                <h1>Wishlist Belanja</h1>
                <p id="wishlistSubtitle">0 item dalam wishlist</p>
            </div>

            <div id="wishlistContainer">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Memuat wishlist...</p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script>
        async function loadWishlist() {
            const container = document.getElementById('wishlistContainer');
            const subtitle = document.getElementById('wishlistSubtitle');

            try {
                const response = await fetch('../api/wishlist.php?action=getAll');
                const result = await response.json();

                if (result.success) {
                    const itemCount = result.items.length;
                    subtitle.textContent = `${itemCount} item dalam wishlist`;

                    if (itemCount === 0) {
                        container.innerHTML = `
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="fas fa-heart"></i>
            </div>
            <h2>Wishlist Anda Kosong</h2>
            <p>Belum ada produk yang ditambahkan ke wishlist</p>
            <a href="products.php" class="btn-primary">Mulai Belanja</a>
        </div>
    `;
                    } else {
                        let html = '<div class="wishlist-grid">';

                        result.items.forEach(item => {
                            html += `
        <div class="wishlist-item" data-product-id="${item.product_id}">
            <button class="remove-btn" onclick="removeFromWishlist(${item.product_id})" title="Hapus dari wishlist">
                <i class="fas fa-times"></i>
            </button>
            <img src="../assets/images/${item.image}" alt="${item.name}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'300\\' height=\\'200\\' viewBox=\\'0 0 300 200\\'%3E%3Crect width=\\'300\\' height=\\'200\\' fill=\\'%23EDF1D6\\'/%3E%3Ctext x=\\'50%25\\' y=\\'50%25\\' font-family=\\'Poppins\\' font-size=\\'16\\' fill=\\'%2340513B\\' text-anchor=\\'middle\\' dy=\\'.3em\\'%3ENo Image%3C/text%3E%3C/svg%3E'">
            <div class="wishlist-content">
                <div class="category">${item.category_name || 'Produk'}</div>
                <h3>${item.name}</h3>
                <div class="rating">
                    <div class="stars">
                        ${generateStars(item.rating)}
                    </div>
                    <span>${item.rating} (${item.sold})</span>
                </div>
                <div class="price">
                    <span class="current-price">Rp ${parseInt(item.price).toLocaleString('id-ID')}</span>
                </div>
                <div class="wishlist-actions">
                    <button class="btn-add-to-cart" onclick="addToCartFromWishlist(${item.product_id})">
                        <i class="fas fa-shopping-cart"></i>
                        Tambah ke Keranjang
                    </button>
                </div>
            </div>
        </div>
    `;
                        });

                        html += '</div>';
                        container.innerHTML = html;
                    }
                } else {
                    container.innerHTML = `
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Gagal Memuat Wishlist</h2>
            <p>${result.error}</p>
            <button onclick="loadWishlist()" class="btn-primary">
                Coba Lagi
            </button>
        </div>
    `;
                }
            } catch (error) {
                console.error('Error loading wishlist:', error);
                container.innerHTML = `
    <div class="empty-cart">
        <div class="empty-cart-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>Terjadi Kesalahan</h2>
        <p>Gagal memuat wishlist. Silakan coba lagi.</p>
        <button onclick="loadWishlist()" class="btn-primary">
            Coba Lagi
        </button>
    </div>
`;
            }
        }

        function generateStars(rating) {
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    starsHtml += '<i class="fas fa-star filled"></i>';
                } else {
                    starsHtml += '<i class="fas fa-star"></i>';
                }
            }
            return starsHtml;
        }

        async function removeFromWishlist(productId) {
            try {
                const response = await fetch('../api/wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&product_id=${productId}`
                });

                const result = await response.json();

                if (result.success) {
                    const item = document.querySelector(`[data-product-id="${productId}"]`);
                    if (item) {
                        item.style.transform = 'scale(0.8)';
                        item.style.opacity = '0';
                        setTimeout(() => {
                            item.remove();
                            const remainingItems = document.querySelectorAll('.wishlist-item');
                            if (remainingItems.length === 0) {
                                loadWishlist();
                            } else {
                                const subtitle = document.getElementById('wishlistSubtitle');
                                subtitle.textContent = `${remainingItems.length} item dalam wishlist`;
                            }
                        }, 300);
                    }

                    updateWishlistCount();
                    showNotification('Produk berhasil dihapus dari wishlist!', 'success');
                } else {
                    showNotification(result.error || 'Gagal menghapus dari wishlist', 'error');
                }
            } catch (error) {
                console.error('Error removing from wishlist:', error);
                showNotification('Terjadi kesalahan saat menghapus dari wishlist', 'error');
            }
        }

        async function addToCartFromWishlist(productId) {
            try {
                const response = await fetch('../api/add-to-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&quantity=1`
                });

                const result = await response.json();

                if (result.success) {
                    updateCartCount();
                    showNotification('Produk berhasil ditambahkan ke keranjang!', 'success');
                } else {
                    showNotification(result.message || 'Gagal menambahkan ke keranjang', 'error');
                }
            } catch (error) {
                console.error('Error adding to cart:', error);
                showNotification('Terjadi kesalahan saat menambahkan ke keranjang', 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadWishlist();
        });
    </script>
</body>

</html>