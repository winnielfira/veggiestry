<header class="main-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="/index.php">
                    <img src="/assets/images/logo.svg" onerror="this.style.display='none'">
                </a>
            </div>

            <div class="search-container">
                <div class="search-bar-wrapper">
                    <input type="text" id="searchInput" placeholder="Cari produk" class="search-input-header">
                    <button onclick="searchProducts()" class="search-btn-header">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>

            <div class="header-actions">

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="wishlist-icon">
                        <a href="/pages/wishlist.php">
                            <i class="fas fa-heart"></i>
                            <span class="wishlist-count" id="wishlistCount">0</span>
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="cart-icon">
                        <a href="/pages/cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count" id="cartCount">0</span>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="profile-section">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="profile-dropdown">
                            <button class="profile-btn" onclick="toggleProfileMenu()">
                                <i class="fas fa-user"></i>
                                <span><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu" id="profileDropdown">
                                <a href="/pages/account.php">
                                    <i class="fas fa-user"></i>
                                    Profil Saya
                                </a>
                                <a href="/pages/wishlist.php">
                                    <i class="fas fa-heart"></i>
                                    Wishlist Saya
                                </a>
                                <a href="/pages/account.php#orders">
                                    <i class="fas fa-shopping-bag"></i>
                                    Pesanan Saya
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="/auth/logout.php">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="auth-buttons">
                            <a href="/auth/login.php" class="btn-login">Login</a>
                            <a href="/auth/register.php" class="btn-register">Daftar</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>


</header>

<style>
    .main-header {
        background: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 0;
        gap: 20px;
    }

    .logo a {
        display: flex;
        align-items: center;
        text-decoration: none;
        color: #40513B;
        font-weight: 700;
        font-size: 24px;
        font-family: 'Poppins', sans-serif;
    }

    .logo {
        width: auto !important;
        height: auto !important;
        flex-shrink: 0;
    }

    .logo img {
        display: block !important;
        height: 45px !important;
        width: 90px !important;
        object-fit: contain !important;
        margin-right: 10px !important;
    }

    .search-container {
        flex: 1;
        max-width: 500px;
        margin: 0 20px;
    }

    .search-bar {
        position: relative;
        display: flex;
        background: #EDF1D6;
        border-radius: 25px;
        overflow: hidden;
    }

    .search-bar input {
        flex: 1;
        padding: 12px 20px;
        border: none;
        background: transparent;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
    }

    .search-bar button {
        padding: 12px 20px;
        background: #609966;
        border: none;
        color: white;
        cursor: pointer;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 20px;
    }


    .cart-icon,
    .wishlist-icon {
        position: relative;
    }

    .cart-icon a,
    .wishlist-icon a {
        display: flex;
        align-items: center;
        color: #40513B;
        text-decoration: none;
        font-size: 20px;
        padding: 10px;
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .cart-icon a:hover {
        background: #EDF1D6;
        color: #609966;
    }

    .wishlist-icon a:hover {
        background: #EDF1D6;
        color: #609966;
    }

    .cart-count,
    .wishlist-count {
        position: absolute;
        top: 0;
        right: 0;
        background: #609966;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        font-family: 'Poppins', sans-serif;
    }

    .profile-dropdown {
        position: relative;
    }

    .profile-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        background: none;
        border: none;
        color: #40513B;
        cursor: pointer;
        font-size: 14px;
        padding: 8px 12px;
        border-radius: 8px;
        transition: background-color 0.3s;
        font-family: 'Poppins', sans-serif;
    }

    .profile-btn:hover {
        background: #EDF1D6;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        min-width: 200px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        color: #40513B;
        text-decoration: none;
        transition: background-color 0.3s;
        font-family: 'Poppins', sans-serif;
    }

    .dropdown-menu a:hover {
        background: #EDF1D6;
    }

    .dropdown-divider {
        height: 1px;
        background: #eee;
        margin: 5px 0;
    }

    .auth-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-login,
    .btn-register {
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
        font-family: 'Poppins', sans-serif;
    }

    .btn-login {
        color: #40513B;
        border: 1px solid #40513B;
    }

    .btn-login:hover {
        background: #40513B;
        color: white;
    }

    .btn-register {
        background: #609966;
        color: white;
    }

    .btn-register:hover {
        background: #40513B;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee;
    }

    .modal-header h3 {
        margin: 0;
        color: #40513B;
        font-family: 'Poppins', sans-serif;
    }

    .close {
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
    }

    .close:hover {
        color: #40513B;
    }

    .modal-body {
        padding: 20px;
    }

    @media (max-width: 768px) {
        .header-content {
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-container {
            order: 3;
            flex-basis: 100%;
            margin: 0;
        }

        .header-actions {
            gap: 15px;
        }
    }

    @media (max-width: 480px) {
        .logo span {
            display: none;
        }

        .profile-btn span {
            display: none;
        }

        .auth-buttons {
            flex-direction: column;
            gap: 5px;
        }
    }

    .main-header .logo {
        width: auto !important;
        height: auto !important;
        border-radius: 0 !important;
        display: flex !important;
        align-items: center !important;
        flex-shrink: 0 !important;
    }

    .main-header .logo a {
        display: flex !important;
        align-items: center !important;
        text-decoration: none !important;
    }

    .main-header .logo img {
        display: block !important;
        height: 50px !important;
        width: 300px !important;
        object-fit: contain !important;
        margin-right: 5px !important;
    }
</style>