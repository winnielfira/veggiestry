<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

$slug = $_GET['slug'] ?? '';
$post = getBlogPostBySlug($conn, $slug);

if (!$post) {
    header('Location: blog.php');
    exit;
}

$other_posts = getOtherBlogPosts($conn, $post['id']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - Veggiestry Blog</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/blog-detail.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/navbar.php'; ?>

    <main class="blog-detail-main">
        <div class="container">
            <nav class="breadcrumb">
                <a href="../index.php">Home</a>
                <span>/</span>
                <a href="blog.php">Blog</a>
                <span>/</span>
                <span><?= htmlspecialchars($post['title']) ?></span>
            </nav>

            <article class="blog-article">
                <header class="article-header">
                    <div class="article-category"><?= $post['category_name'] ?></div>
                    <h1><?= htmlspecialchars($post['title']) ?></h1>
                    <div class="article-meta">
                        <span class="article-author">
                            <i class="fas fa-user"></i>
                            <?= $post['author'] ?>
                        </span>
                        <span class="article-date">
                            <i class="fas fa-calendar"></i>
                            <?= date('d M Y', strtotime($post['created_at'])) ?>
                        </span>
                    </div>
                </header>

                <div class="article-image">
                    <img src="../assets/images/<?= $post['featured_image'] ?>" alt="<?= $post['title'] ?>">
                </div>

                <div class="article-content">
                    <?= $post['content'] ?>
                </div>
            </article>

            <?php if (count($other_posts) > 0): ?>
                <section class="related-posts">
                    <h2>Artikel Lainnya</h2>
                    <div class="related-grid">
                        <?php foreach ($other_posts as $other): ?>
                            <article class="related-card" onclick="window.location.href='blog-detail.php?slug=<?= $other['slug'] ?>'">
                                <div class="related-image">
                                    <img src="../assets/images/<?= $other['featured_image'] ?>" alt="<?= $other['title'] ?>">
                                </div>
                                <div class="related-content">
                                    <div class="related-category"><?= $other['category_name'] ?></div>
                                    <h3><?= htmlspecialchars($other['title']) ?></h3>
                                    <div class="related-meta">
                                        <span><?= date('d M Y', strtotime($other['created_at'])) ?></span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/blog-detail.js"></script>
</body>

</html>