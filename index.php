<?php
session_start();
include 'config/database.php';
include 'includes/functions.php';

$featured_products = getFeaturedProducts($conn, 10);
$categories = getCategories($conn, 10);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veggiestry - Belanja Sayuran Segar Online</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/navbar.php'; ?>

    <main>
        <section class="banner-section">
            <div class="banner-container">
                <div class="banner-slide active" style="background-image: url('assets/images/spanduk1.svg'); background-size: cover; background-position: center;">
                    <div class="banner-content">
                        <button class="btn-primary" onclick="window.location.href='pages/products.php'">
                            Belanja Sekarang
                        </button>
                    </div>
                </div>

                <div class="banner-slide" style="background-image: url('assets/images/spanduk2.svg'); background-size: cover; background-position: center;">
                    <div class="banner-content">
                        <button class="btn-primary" onclick="window.location.href='pages/products.php'">
                            Belanja Sekarang
                        </button>
                    </div>
                </div>
            </div>

            <div class="banner-dots">
                <span class="dot active" onclick="goToSlide(1)"></span>
                <span class="dot" onclick="goToSlide(2)"></span>
            </div>
        </section>

        <section class="categories-section">
            <div class="container">
                <h2>Kategori Produk</h2>
                <div class="categories-slider">
                    <button class="slider-btn prev" onclick="slideCategories(-1)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="categories-container">
                        <?php foreach ($categories as $category): ?>
                            <div class="category-card" onclick="window.location.href='pages/products.php?category=<?= $category['slug'] ?>'">
                                <img src="assets/images/<?= $category['image'] ?>" alt="<?= $category['name'] ?>">
                                <h3><?= $category['name'] ?></h3>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="slider-btn next" onclick="slideCategories(1)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>

        <section class="promo-banners">
            <div class="container">
                <div class="promo-grid">
                    <div class="promo-card">
                        <img src="assets/images/spanduk3.svg" alt="Banner Promo 1">
                        <div class="promo-content">
                            <button class="btn-secondary" onclick="window.location.href='pages/products.php?category=sayuran'">
                                Shop Now
                            </button>
                        </div>
                    </div>

                    <div class="promo-card">
                        <img src="assets/images/spanduk4.svg" alt="Banner Promo 2">
                        <div class="promo-content">
                            <button class="btn-secondary" onclick="window.location.href='pages/products.php?category=buah-buahan'">
                                Shop Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="top-products">
            <div class="container">
                <h2>Top 10 Penjualan Terbanyak Bulan Ini</h2>
                <div class="products-grid">
                    <?php foreach ($featured_products as $product): ?>
                        <div class="product-card">
                            <img src="assets/images/<?= $product['image'] ?>" alt="<?= $product['name'] ?>">
                            <div class="product-info">
                                <span class="product-category"><?= $product['category_name'] ?></span>
                                <h3><?= $product['name'] ?></h3>
                                <div class="product-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $product['rating'] ? 'filled' : '' ?>"></i>
                                    <?php endfor; ?>
                                    <span><?= number_format($product['rating'], 1) ?> (<?= $product['sold'] ?>)</span>
                                </div>
                                <div class="product-price">
                                    <span class="price">Rp <?= number_format($product['price'], 0, ',', '.') ?></span>
                                    <div class="product-actions">
                                        <?php if (isset($_SESSION['user_id'])): ?>
                                            <button class="wishlist-btn" onclick="event.stopPropagation(); toggleWishlist(<?= $product['id'] ?>)" data-product-id="<?= $product['id'] ?>">
                                                <i class="far fa-heart"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="add-btn" onclick="event.stopPropagation(); addToCart(<?= $product['id'] ?>)">
                                            +Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="why-choose-us">
            <div class="container">
                <div class="features-grid">
                    <div class="feature-card">
                        <i class="fas fa-truck"></i>
                        <h3>Pengiriman Cepat</h3>
                        <p>Pengiriman same day untuk area Jakarta</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-leaf"></i>
                        <h3>Produk Segar</h3>
                        <p>Langsung dari petani terpercaya</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Kualitas Terjamin</h3>
                        <p>Garansi uang kembali jika tidak puas</p>
                    </div>
                    <div class="feature-card">
                        <i class="fas fa-headset"></i>
                        <h3>Customer Service 24/7</h3>
                        <p>Siap membantu kapan saja</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/home.js"></script>
</body>

</html>