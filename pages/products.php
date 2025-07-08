<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$rating = $_GET['rating'] ?? '';
$page = $_GET['page'] ?? 1;

$products = getProducts($conn, $search, $category, $min_price, $max_price, $rating, $page);
$categories = getCategories($conn);
$total_products = getTotalProducts($conn, $search, $category, $min_price, $max_price, $rating);
$total_pages = ceil($total_products / 12);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Produk - Veggiestry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/products.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <main class="products-main">
        <div class="container">
            <div class="products-layout">
                <aside class="filters-sidebar">
                    <div class="filter-section">
                        <h3>Filter Harga</h3>
                        <form class="price-filter" method="GET" id="priceFilterForm">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                            <input type="hidden" name="rating" value="<?= htmlspecialchars($rating) ?>">

                            <div class="price-inputs">
                                <div class="price-input-group">
                                    <label for="min_price">Harga Minimum</label>
                                    <input type="number"
                                        id="min_price"
                                        name="min_price"
                                        placeholder="0"
                                        value="<?= htmlspecialchars($min_price) ?>"
                                        min="0"
                                        step="1000">
                                </div>

                                <div class="price-input-group">
                                    <label for="max_price">Harga Maksimum</label>
                                    <input type="number"
                                        id="max_price"
                                        name="max_price"
                                        placeholder="1000000"
                                        value="<?= htmlspecialchars($max_price) ?>"
                                        min="0"
                                        step="1000">
                                </div>
                            </div>

                            <button type="submit" class="btn-apply">
                                <i class="fas fa-filter"></i> Terapkan
                            </button>
                        </form>
                    </div>

                    <div class="filter-section">
                        <h3>Rating Produk</h3>
                        <form class="rating-filter" method="GET">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                            <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                            <input type="hidden" name="min_price" value="<?= htmlspecialchars($min_price) ?>">
                            <input type="hidden" name="max_price" value="<?= htmlspecialchars($max_price) ?>">

                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <label class="rating-option">
                                    <input type="radio" name="rating" value="<?= $i ?>" <?= $rating == $i ? 'checked' : '' ?> onchange="this.form.submit()">
                                    <div class="rating-content">
                                        <div class="stars">
                                            <?php for ($j = 1; $j <= 5; $j++): ?>
                                                <i class="fas fa-star <?= $j <= $i ? 'filled' : '' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endfor; ?>
                        </form>
                    </div>
                </aside>

                <div class="products-content">
                    <div class="products-header">
                        <h1>
                            <?php if ($search): ?>
                                Hasil pencarian "<?= htmlspecialchars($search) ?>"
                            <?php elseif ($category): ?>
                                Kategori: <?= htmlspecialchars($category) ?>
                            <?php else: ?>
                                Semua Produk
                            <?php endif; ?>
                        </h1>
                        <p><?= $total_products ?> produk ditemukan</p>
                    </div>

                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <img src="../assets/images/<?= $product['image'] ?>" alt="<?= $product['name'] ?>">
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

                    <?php if (empty($products)): ?>
                        <div class="no-products">
                            <div class="no-products-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>Tidak ada produk ditemukan</h3>
                            <p>Coba ubah filter pencarian atau <a href="products.php">lihat semua produk</a></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-btn">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-btn">1</a>
                                <?php if ($start_page > 2): ?>
                                    <span class="page-dots">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                    class="page-btn <?= $i == $page ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="page-dots">...</span>
                                <?php endif; ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="page-btn"><?= $total_pages ?></a>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-btn">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const minPriceInput = document.getElementById('min_price');
            const maxPriceInput = document.getElementById('max_price');

            function validatePriceInputs() {
                const minPrice = parseInt(minPriceInput.value) || 0;
                const maxPrice = parseInt(maxPriceInput.value) || 0;

                minPriceInput.setCustomValidity('');
                maxPriceInput.setCustomValidity('');

                if (minPrice > 0 && maxPrice > 0 && minPrice > maxPrice) {
                    minPriceInput.setCustomValidity('Harga minimum tidak boleh lebih besar dari harga maksimum');
                    maxPriceInput.setCustomValidity('Harga maksimum tidak boleh lebih kecil dari harga minimum');
                }
            }

            if (minPriceInput && maxPriceInput) {
                minPriceInput.addEventListener('input', validatePriceInputs);
                maxPriceInput.addEventListener('input', validatePriceInputs);
            }
        });
    </script>
</body>

</html>