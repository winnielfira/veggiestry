<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = $_GET['page'] ?? 1;

$blog_posts = getBlogPosts($conn, $search, $category, $page);
$blog_categories = getBlogCategories($conn);
$total_posts = getTotalBlogPosts($conn, $search, $category);
$total_pages = ceil($total_posts / 9);

echo "<!-- DEBUG INFO -->";
echo "<!-- Total posts: " . $total_posts . " -->";
echo "<!-- Blog posts count: " . count($blog_posts) . " -->";
echo "<!-- Categories count: " . count($blog_categories) . " -->";
echo "<!-- Search: '" . $search . "' -->";
echo "<!-- Category: '" . $category . "' -->";
echo "<!-- Page: " . $page . " -->";

if (empty($blog_posts)) {
    echo "<!-- ERROR: No blog posts found -->";
    $test_query = "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'";
    $test_result = $conn->query($test_query);
    $test_count = $test_result->fetch()['total'];
    echo "<!-- Direct query result: " . $test_count . " published posts -->";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Resep - Veggiestry</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/blog.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <main class="blog-main">
        <div class="blog-banner-wrapper">
            <div class="blog-header"></div>
        </div>

        <div class="container">
            <div class="blog-search">
                <form method="GET" class="search-form">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                    <div class="search-container">
                        <input type="text" name="search" placeholder="Cari artikel resep makanan atau fakta kesehatan" value="<?= htmlspecialchars($search) ?>" class="search-input">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="blog-categories">
                <div class="category-filters">
                    <a href="blog.php" class="category-filter <?= !$category ? 'active' : '' ?>">Semua</a>
                    <?php foreach ($blog_categories as $cat): ?>
                        <a href="?category=<?= $cat['slug'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                            class="category-filter <?= $category == $cat['slug'] ? 'active' : '' ?>">
                            <?= $cat['name'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="blog-content">
                <?php if ($search || $category): ?>
                    <div class="blog-results">
                        <h2>
                            <?php if ($search): ?>
                                Hasil pencarian "<?= htmlspecialchars($search) ?>"
                            <?php elseif ($category): ?>
                                Kategori: <?= htmlspecialchars($category) ?>
                            <?php endif; ?>
                        </h2>
                        <p><?= $total_posts ?> artikel ditemukan</p>
                    </div>
                <?php endif; ?>

                <div class="blog-grid">
                    <?php foreach ($blog_posts as $post): ?>
                        <article class="blog-card" onclick="window.location.href='blog-detail.php?slug=<?= $post['slug'] ?>'">
                            <div class="blog-image">
                                <img src="../assets/images/<?= $post['featured_image'] ?>" alt="<?= $post['title'] ?>">
                                <div class="blog-category"><?= $post['category_name'] ?></div>
                            </div>
                            <div class="blog-content-card">
                                <h3><?= htmlspecialchars($post['title']) ?></h3>
                                <p><?= htmlspecialchars($post['excerpt']) ?></p>
                                <div class="blog-meta">
                                    <span><i class="fas fa-user"></i> <?= $post['author'] ?></span>
                                    <span><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($post['created_at'])) ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-btn">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                                class="page-btn <?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-btn">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
</body>

</html>