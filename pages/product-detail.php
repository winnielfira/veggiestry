<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

$product_id = $_GET['id'] ?? 0;
$product = getProductById($conn, $product_id);
$related_products = getRelatedProducts($conn, $product['category_id'], $product_id, 8);

if (!$product) {
    header('Location: products.php');
    exit;
}

$available_stock = $product['available_stock'];
$is_out_of_stock = $product['is_out_of_stock'];
$is_low_stock = $product['is_low_stock'];
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Veggiestry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/product-detail.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script>
        window.productData = {
            id: <?= $product['id'] ?>,
            name: '<?= addslashes($product['name']) ?>',
            availableStock: <?= $available_stock ?>,
            isOutOfStock: <?= $is_out_of_stock ? 'true' : 'false' ?>,
            isLowStock: <?= $is_low_stock ? 'true' : 'false' ?>
        };
    </script>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <main class="product-detail-main">
        <div class="container">
            <nav class="breadcrumb">
                <a href="../index.php">Home</a>
                <span>/</span>
                <a href="products.php">Produk</a>
                <span>/</span>
                <span><?= htmlspecialchars($product['name']) ?></span>
            </nav>

            <div class="product-detail">
                <div class="product-images">
                    <div class="main-image">
                        <img id="mainImage" src="../assets/images/products/<?= $product['image'] ?>" alt="<?= $product['name'] ?>">
                    </div>
                    <div class="thumbnail-images">
                        <img class="thumbnail active" src="../assets/images/products/<?= $product['image'] ?>" alt="<?= $product['name'] ?>" onclick="changeMainImage(this.src)">
                        <img class="thumbnail" src="../assets/images/products/<?= $product['image'] ?>" alt="<?= $product['name'] ?>" onclick="changeMainImage(this.src)">
                        <img class="thumbnail" src="../assets/images/products/<?= $product['image'] ?>" alt="<?= $product['name'] ?>" onclick="changeMainImage(this.src)">
                        <img class="thumbnail" src="../assets/images/products/<?= $product['image'] ?>" alt="<?= $product['name'] ?>" onclick="changeMainImage(this.src)">
                    </div>
                </div>

                <div class="product-info">
                    <div class="product-header">
                        <span class="product-category"><?= $product['category_name'] ?></span>
                        <h1><?= htmlspecialchars($product['name']) ?></h1>
                        <div class="product-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $product['rating'] ? 'filled' : '' ?>"></i>
                            <?php endfor; ?>
                            <span><?= $product['rating'] ?> (<?= $product['sold'] ?> terjual)</span>
                        </div>
                        <div class="product-price">
                            <span class="current-price">Rp <?= number_format($product['price'], 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <div class="product-actions">
                        <div class="quantity-selector">
                            <label>Jumlah:</label>
                            <div class="quantity-controls">
                                <button type="button" onclick="changeQuantity(-1)" <?= $is_out_of_stock ? 'disabled' : '' ?>>-</button>
                                <input type="number" id="quantity" value="1" min="1" max="<?= $available_stock ?>"
                                    <?= $is_out_of_stock ? 'disabled' : '' ?>>
                                <button type="button" onclick="changeQuantity(1)" <?= $is_out_of_stock ? 'disabled' : '' ?>>+</button>
                            </div>

                            <span class="stock-info">Stok Tersedia: <strong id="availableStockDisplay"><?= $available_stock ?></strong></span>

                            <?php if ($is_out_of_stock): ?>
                                <span class="out-of-stock">Stok Habis</span>
                            <?php elseif ($is_low_stock): ?>
                                <span class="low-stock">Stok Terbatas! Tersisa <?= $available_stock ?> item</span>
                            <?php else: ?>
                                <span class="stock-good">Stok Tersedia</span>
                            <?php endif; ?>
                        </div>

                        <div class="action-buttons">
                            <button class="btn-wishlist" onclick="toggleWishlist(<?= $product['id'] ?>)" data-product-id="<?= $product['id'] ?>">
                                <i class="far fa-heart"></i>
                                <span class="wishlist-text">Tambah ke Wishlist</span>
                            </button>
                            <button class="btn-add-cart" onclick="addToCartDetail(<?= $product['id'] ?>)"
                                <?= $is_out_of_stock ? 'disabled class="disabled"' : '' ?>>
                                <i class="fas fa-<?= $is_out_of_stock ? 'times' : 'shopping-cart' ?>"></i>
                                <?= $is_out_of_stock ? 'Stok Habis' : 'Tambah ke Keranjang' ?>
                            </button>
                            <button class="btn-buy-now" onclick="buyNow(<?= $product['id'] ?>)"
                                <?= $is_out_of_stock ? 'disabled class="disabled"' : '' ?>>
                                <?= $is_out_of_stock ? 'Stok Habis' : 'Beli Sekarang' ?>
                            </button>
                        </div>

                        <div class="product-features">
                            <div class="feature">
                                <i class="fas fa-truck"></i>
                                <span>Gratis ongkir min. 100rb</span>
                            </div>
                            <div class="feature">
                                <i class="fas fa-leaf"></i>
                                <span>Produk segar terjamin</span>
                            </div>
                            <div class="feature">
                                <i class="fas fa-shield-alt"></i>
                                <span>Garansi uang kembali</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="product-description">
                    <div class="description-tabs">
                        <button class="tab-btn active" onclick="showTab('description')">Deskripsi</button>
                        <button class="tab-btn" onclick="showTab('specifications')">Spesifikasi</button>
                        <button class="tab-btn" onclick="showTab('reviews')">Ulasan (<?= $product['review_count'] ?>)</button>
                    </div>

                    <div class="tab-content">
                        <div id="description" class="tab-pane active">
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>
                        <div id="specifications" class="tab-pane">
                            <table class="specs-table">
                                <tr>
                                    <td>Kategori</td>
                                    <td><?= $product['category_name'] ?></td>
                                </tr>
                                <tr>
                                    <td>Stok Awal</td>
                                    <td><?= $product['stock'] ?> item</td>
                                </tr>
                                <tr>
                                    <td>Terjual</td>
                                    <td><?= $product['sold'] ?> item</td>
                                </tr>
                                <tr>
                                    <td>Stok Tersedia</td>
                                    <td><strong><?= $available_stock ?> item</strong></td>
                                </tr>
                                <tr>
                                    <td>Berat</td>
                                    <td><?= $product['weight'] ?> gram</td>
                                </tr>
                                <tr>
                                    <td>Kondisi</td>
                                    <td>Baru</td>
                                </tr>
                                <tr>
                                    <td>Asal</td>
                                    <td><?= $product['origin'] ?></td>
                                </tr>
                            </table>
                        </div>
                        <div id="reviews" class="tab-pane">
                            <div class="reviews-summary">
                                <div class="rating-overview">
                                    <div class="rating-score">
                                        <span class="score"><?= $product['rating'] ?></span>
                                        <div class="stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?= $i <= $product['rating'] ? 'filled' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="review-count"><?= $product['review_count'] ?> ulasan</span>
                                    </div>
                                </div>
                            </div>
                            <div class="reviews-list">
                                <div class="review-item">
                                    <div class="reviewer-info">
                                        <strong>Sari M.</strong>
                                        <div class="review-rating">
                                            <i class="fas fa-star filled"></i>
                                            <i class="fas fa-star filled"></i>
                                            <i class="fas fa-star filled"></i>
                                            <i class="fas fa-star filled"></i>
                                            <i class="fas fa-star filled"></i>
                                        </div>
                                    </div>
                                    <p>Produk sangat segar dan berkualitas. Pengiriman cepat dan packaging rapi.</p>
                                    <span class="review-date">2 hari yang lalu</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="related-products">
                    <h2>Produk Terkait</h2>
                    <div class="products-grid">
                        <?php foreach ($related_products as $related): ?>
                            <div class="product-card" onclick="window.location.href='product-detail.php?id=<?= $related['id'] ?>'">
                                <img src="../assets/images/<?= $related['image'] ?>" alt="<?= $related['name'] ?>">
                                <div class="product-info">
                                    <span class="product-category"><?= $related['category_name'] ?></span>
                                    <h3><?= $related['name'] ?></h3>
                                    <div class="product-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $related['rating'] ? 'filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                        <span><?= $related['rating'] ?> (<?= $related['sold'] ?>)</span>
                                    </div>
                                    <div class="product-price">
                                        <span class="price">Rp <?= number_format($related['price'], 0, ',', '.') ?></span>
                                        <?php if ($related['available_stock'] > 0): ?>
                                            <button class="add-btn" onclick="event.stopPropagation(); addToCart(<?= $related['id'] ?>)">
                                                +Add
                                            </button>
                                        <?php else: ?>
                                            <button class="add-btn disabled" disabled>
                                                Habis
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/product-detail.js"></script>
</body>

</html>