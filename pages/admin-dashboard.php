<?php
session_start();

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

include '../config/database.php';

$admin_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Veggiestry</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="admin-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="admin-info">
                    <h3><?php echo htmlspecialchars($admin_name); ?></h3>
                    <p>Administrator</p>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="#" class="nav-item active" onclick="switchTab('dashboard')">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item" onclick="switchTab('products')">
                    <i class="fas fa-box"></i>
                    <span>Produk</span>
                </a>
                <a href="#" class="nav-item" onclick="switchTab('categories')">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
                <a href="#" class="nav-item" onclick="switchTab('orders')">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pesanan</span>
                </a>
                <a href="#" class="nav-item" onclick="switchTab('customers')">
                    <i class="fas fa-users"></i>
                    <span>Pelanggan</span>
                </a>
                <a href="../auth/logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <main class="main-content">
            <div id="dashboard" class="tab-content active">
                <div class="content-header">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                    <p>Selamat datang kembali, <?php echo htmlspecialchars($admin_name); ?>!</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-sales">0</h3>
                            <p>Total Penjualan</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-orders">0</h3>
                            <p>Total Pesanan</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="total-customers">0</h3>
                            <p>Total Pelanggan</p>
                        </div>
                    </div>
                </div>

                <div class="order-history-section">
                    <div class="section-header">
                        <h2><i class="fas fa-history"></i> Riwayat Pesanan</h2>
                    </div>

                    <div class="table-container">
                        <table class="data-table" id="orderHistoryTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Nama Produk</th>
                                    <th>Tanggal Order</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="loading-row">
                                        <i class="fas fa-spinner fa-spin"></i> Memuat data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="products" class="tab-content">
                <div class="content-header">
                    <h1><i class="fas fa-box"></i> Kelola Produk</h1>
                    <p>Tambah, edit, dan kelola produk</p>
                </div>

                <div class="action-bar">
                    <button class="btn-primary" onclick="showAddProductModal()">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </button>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="productSearch" placeholder="Cari produk" onkeyup="searchProducts()">
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table no-image" id="productsTable">
                        <thead>
                            <tr>
                                <th>Nama Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="loading-row">
                                    <i class="fas fa-spinner fa-spin"></i> Memuat produk...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="categories" class="tab-content">
                <div class="content-header">
                    <h1><i class="fas fa-tags"></i> Kelola Kategori</h1>
                    <p>Tambah, edit, dan kelola kategori produk</p>
                </div>

                <div class="action-bar">
                    <button class="btn-primary" onclick="showAddCategoryModal()">
                        <i class="fas fa-plus"></i> Tambah Kategori
                    </button>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="categorySearch" placeholder="Cari kategori" onkeyup="searchCategories()">
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table no-icon" id="categoriesTable">
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th>Jumlah Produk</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="3" class="loading-row">
                                    <i class="fas fa-spinner fa-spin"></i> Memuat kategori...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="orders" class="tab-content">
                <div class="content-header">
                    <h1><i class="fas fa-shopping-cart"></i> Kelola Pesanan</h1>
                    <p>Kelola dan update status pesanan pelanggan</p>
                </div>

                <div class="order-tabs">
                    <button class="tab-btn active" onclick="switchOrderTab('list')">
                        <i class="fas fa-list"></i> Daftar Pesanan
                    </button>
                    <button class="tab-btn" onclick="switchOrderTab('single')">
                        <i class="fas fa-file-alt"></i> Detail Pesanan
                    </button>
                </div>

                <div id="order-list" class="order-tab-content active">
                    <div class="table-container">
                        <table class="data-table" id="orderListTable">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Tanggal</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Update Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="loading-row">
                                        <i class="fas fa-spinner fa-spin"></i> Memuat pesanan...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="order-single" class="order-tab-content">
                    <div class="search-order">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="orderIdSearch" placeholder="Masukkan Order ID" onkeyup="searchOrderById()">
                        </div>
                    </div>

                    <div id="orderDetailContainer">
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>Cari Order by ID</h3>
                            <p>Masukkan Order ID untuk melihat detail pesanan</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="customers" class="tab-content">
                <div class="content-header">
                    <h1><i class="fas fa-users"></i> Kelola Pelanggan</h1>
                    <p>Informasi dan data pelanggan</p>
                </div>

                <div class="action-bar">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="customerSearch" placeholder="Cari pelanggan" onkeyup="searchCustomers()">
                    </div>
                </div>

                <div class="table-container">
                    <table class="data-table" id="customersTable">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Purchase Date</th>
                                <th>Phone</th>
                                <th>Total Spent</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="loading-row">
                                    <i class="fas fa-spinner fa-spin"></i> Memuat pelanggan...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin-dashboard.js"></script>
</body>

</html>