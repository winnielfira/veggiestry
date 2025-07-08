<?php
$base_url = '';

$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

function isActive($page)
{
    global $current_page, $current_dir;

    switch ($page) {
        case 'home':
            return ($current_page === 'index.php' && $current_dir !== 'pages') ? 'active' : '';
        case 'products':
            return ($current_page === 'products.php') ? 'active' : '';
        case 'blog':
            return ($current_page === 'blog.php' || $current_page === 'blog-detail.php') ? 'active' : '';
        case 'account':
            return ($current_page === 'account.php') ? 'active' : '';
        default:
            return '';
    }
}
?>
<nav class="main-navbar">
    <div class="container">
        <ul class="nav-menu">
            <li>
                <a href="<?= $base_url ?>/index.php" class="nav-link <?= isActive('home') ?>">
                    <span>HOME</span>
                </a>
            </li>
            <li>
                <a href="<?= $base_url ?>/pages/products.php" class="nav-link <?= isActive('products') ?>">
                    <span>SEMUA PRODUK</span>
                </a>
            </li>
            <li>
                <a href="<?= $base_url ?>/pages/blog.php" class="nav-link <?= isActive('blog') ?>">
                    <span>BLOG</span>
                </a>
            </li>
            <li>
                <a href="<?= $base_url ?>/pages/account.php" class="nav-link <?= isActive('account') ?>">
                    <span>ACCOUNT</span>
                </a>
            </li>
        </ul>
    </div>
</nav>