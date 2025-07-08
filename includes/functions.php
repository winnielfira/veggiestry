<?php

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function getCurrentUser($conn)
{
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ../auth/login.php');
        exit;
    }
}

function isAdmin()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isCustomer()
{
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'customer';
}

function redirectBasedOnRole()
{
    if (isAdmin()) {
        header('Location: ../pages/admin-dashboard.php');
    } else {
        header('Location: ../pages/account.php');
    }
    exit;
}

function getFeaturedProducts($conn, $limit = 10)
{
    $sql = "
        SELECT p.*, c.name as category_name,
               (p.stock - COALESCE(p.sold, 0)) as available_stock
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' AND p.sold > 15
        ORDER BY p.sold DESC 
        LIMIT " . intval($limit);

    $stmt = $conn->query($sql);
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        if (!empty($product['slug'])) {
            $product['image'] = $product['slug'] . '.jpg';
        } else {
            $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $product['name']));
            $product['image'] = $clean_name . '.jpg';
        }

        $product['available_stock'] = max(0, intval($product['available_stock']));
        $product['is_out_of_stock'] = ($product['available_stock'] <= 0);
        $product['is_low_stock'] = ($product['available_stock'] > 0 && $product['available_stock'] <= 5);
    }

    return $products;
}

function getCategories($conn, $limit = null)
{
    $sql = "SELECT * FROM categories ORDER BY name";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $stmt = $conn->query($sql);
    $categories = $stmt->fetchAll();

    $image_mapping = [
        1 => 'wortel.jpg',
        2 => 'apel.jpg',
        3 => 'telur-ayam.jpg',
        4 => 'mie-instan.jpg',
        5 => 'beras.jpg',
        6 => 'susu-sapi.jpg',
        7 => 'saos-sambal.jpg',
        8 => 'wafer-vanilla.jpg',
        9 => 'obat-batuk.jpg',
        10 => 'pembersih-kaca.jpg',
    ];

    foreach ($categories as &$category) {
        if (isset($image_mapping[$category['id']])) {
            $category['image'] = $image_mapping[$category['id']];
        } else {
            $auto_image = $category['slug'] . '.jpg';

            $category['image'] = 'default-category.jpg';
        }
    }

    return $categories;
}

function getProducts($conn, $search = '', $category = '', $min_price = '', $max_price = '', $rating = '', $page = 1, $limit = 12)
{
    $offset = ($page - 1) * $limit;
    $conditions = ["p.status = 'active'"];
    $params = [];

    if ($search) {
        $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($category) {
        $conditions[] = "c.slug = ?";
        $params[] = $category;
    }

    if ($min_price) {
        $conditions[] = "p.price >= ?";
        $params[] = $min_price;
    }

    if ($max_price) {
        $conditions[] = "p.price <= ?";
        $params[] = $max_price;
    }

    if ($rating) {
        if ($rating == 1) {
        } else {
            $conditions[] = "p.rating >= ?";
            $params[] = $rating;
        }
    }

    $where = implode(' AND ', $conditions);

    $sql = "
        SELECT p.*, c.name as category_name,
               (p.stock - COALESCE(p.sold, 0)) as available_stock
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $where 
        ORDER BY p.created_at DESC 
        LIMIT " . intval($limit) . " OFFSET " . intval($offset);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        if (!empty($product['slug'])) {
            $product['image'] = $product['slug'] . '.jpg';
        } else {
            $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $product['name']));
            $product['image'] = $clean_name . '.jpg';
        }

        $product['available_stock'] = max(0, intval($product['available_stock']));
        $product['is_out_of_stock'] = ($product['available_stock'] <= 0);
        $product['is_low_stock'] = ($product['available_stock'] > 0 && $product['available_stock'] <= 5);
    }

    return $products;
}

function getTotalProducts($conn, $search = '', $category = '', $min_price = '', $max_price = '', $rating = '')
{
    $conditions = ["p.status = 'active'"];
    $params = [];

    if ($search) {
        $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($category) {
        $conditions[] = "c.slug = ?";
        $params[] = $category;
    }

    if ($min_price) {
        $conditions[] = "p.price >= ?";
        $params[] = $min_price;
    }

    if ($max_price) {
        $conditions[] = "p.price <= ?";
        $params[] = $max_price;
    }

    if ($rating && $rating > 1) {
        $conditions[] = "p.rating >= ?";
        $params[] = $rating;
    }

    $where = implode(' AND ', $conditions);

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $where
    ");
    $stmt->execute($params);
    return $stmt->fetch()['total'];
}

function getProductById($conn, $id)
{
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name,
               (p.stock - COALESCE(p.sold, 0)) as available_stock
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND p.status = 'active'
    ");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        if (!empty($product['slug'])) {
            $product['image'] = $product['slug'] . '.jpg';
        } else {
            $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $product['name']));
            $product['image'] = $clean_name . '.jpg';
        }

        $product['available_stock'] = max(0, intval($product['available_stock']));
        $product['is_out_of_stock'] = ($product['available_stock'] <= 0);
        $product['is_low_stock'] = ($product['available_stock'] > 0 && $product['available_stock'] <= 5);
    }

    return $product;
}

function getRelatedProducts($conn, $category_id, $exclude_id, $limit = 8)
{
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name,
               (p.stock - COALESCE(p.sold, 0)) as available_stock
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
        ORDER BY RAND() 
        LIMIT ?
    ");
    $stmt->execute([$category_id, $exclude_id, $limit]);
    $products = $stmt->fetchAll();

    foreach ($products as &$product) {
        if (!empty($product['slug'])) {
            $product['image'] = $product['slug'] . '.jpg';
        } else {
            $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $product['name']));
            $product['image'] = $clean_name . '.jpg';
        }

        $product['available_stock'] = max(0, intval($product['available_stock']));
        $product['is_out_of_stock'] = ($product['available_stock'] <= 0);
        $product['is_low_stock'] = ($product['available_stock'] > 0 && $product['available_stock'] <= 5);
    }

    return $products;
}

function getBlogPosts($conn, $search = '', $category = '', $page = 1, $limit = 9)
{
    $offset = ($page - 1) * $limit;
    $conditions = ["bp.status = 'published'"];
    $params = [];

    if ($search) {
        $conditions[] = "(bp.title LIKE ? OR bp.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($category) {
        $conditions[] = "bc.slug = ?";
        $params[] = $category;
    }

    $where = implode(' AND ', $conditions);

    $sql = "SELECT bp.*, bc.name as category_name 
            FROM blog_posts bp 
            LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
            WHERE " . $where . " 
            ORDER BY bp.created_at DESC 
            LIMIT " . intval($limit) . " OFFSET " . intval($offset);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    foreach ($posts as &$post) {
        if (!empty($post['slug'])) {
            $post['featured_image'] = $post['slug'] . '.jpg';
        } else {
            $clean_title = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $post['title']));
            $post['featured_image'] = $clean_title . '.jpg';
        }

        $post['excerpt'] = substr(strip_tags($post['content']), 0, 150) . '...';
    }

    return $posts;
}

function getTotalBlogPosts($conn, $search = '', $category = '')
{
    $conditions = ["bp.status = 'published'"];
    $params = [];

    if ($search) {
        $conditions[] = "(bp.title LIKE ? OR bp.content LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($category) {
        $conditions[] = "bc.slug = ?";
        $params[] = $category;
    }

    $where = implode(' AND ', $conditions);

    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM blog_posts bp 
        LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
        WHERE $where
    ");
    $stmt->execute($params);
    return $stmt->fetch()['total'];
}

function getBlogPostBySlug($conn, $slug)
{
    $stmt = $conn->prepare("
        SELECT bp.*, bc.name as category_name 
        FROM blog_posts bp 
        LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
        WHERE bp.slug = ? AND bp.status = 'published'
    ");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();

    if ($post) {
        if (!empty($post['slug'])) {
            $post['featured_image'] = $post['slug'] . '.jpg';
        } else {
            $clean_title = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $post['title']));
            $post['featured_image'] = $clean_title . '.jpg';
        }
    }

    return $post;
}

function getBlogCategories($conn)
{
    $stmt = $conn->query("SELECT * FROM blog_categories ORDER BY name");
    return $stmt->fetchAll();
}

function getOtherBlogPosts($conn, $exclude_id)
{
    $stmt = $conn->prepare("
        SELECT bp.*, bc.name as category_name 
        FROM blog_posts bp 
        LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
        WHERE bp.status = 'published' AND bp.id != ?
        ORDER BY bp.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$exclude_id]);
    $posts = $stmt->fetchAll();

    foreach ($posts as &$post) {
        if (!empty($post['slug'])) {
            $post['featured_image'] = $post['slug'] . '.jpg';
        } else {
            $clean_title = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $post['title']));
            $post['featured_image'] = $clean_title . '.jpg';
        }
    }

    return $posts;
}

function getCartItems($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.stock, p.slug
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND p.status = 'active'
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    foreach ($cart_items as &$item) {
        if (!empty($item['slug'])) {
            $item['image'] = $item['slug'] . '.jpg';
        } else {
            $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $item['name']));
            $item['image'] = $clean_name . '.jpg';
        }
    }

    return $cart_items;
}

function addToCart($conn, $user_id, $product_id, $quantity = 1)
{
    $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$quantity, $user_id, $product_id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $product_id, $quantity]);
    }
}

function updateCartQuantity($conn, $user_id, $product_id, $quantity)
{
    if ($quantity <= 0) {
        return removeFromCart($conn, $user_id, $product_id);
    }

    $stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
    return $stmt->execute([$quantity, $user_id, $product_id]);
}

function removeFromCart($conn, $user_id, $product_id)
{
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    return $stmt->execute([$user_id, $product_id]);
}

function clearCart($conn, $user_id)
{
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    return $stmt->execute([$user_id]);
}

function getCartTotal($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT SUM(c.quantity * p.price) as total 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getUserOrders($conn, $user_id, $limit = null)
{
    $sql = "SELECT o.*, 
                   (o.total_amount + COALESCE(o.shipping_cost, 0)) as grand_total,
                   COALESCE(o.user_confirmed, FALSE) as user_confirmed
            FROM orders o 
            WHERE o.user_id = ? 
            ORDER BY o.created_at DESC";
    $params = [$user_id];

    if ($limit !== null) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllUserOrders($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               GROUP_CONCAT(p.name SEPARATOR ', ') as product_names,
               (o.total_amount + COALESCE(o.shipping_cost, 0)) as grand_total
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOrderDetails($conn, $order_id, $user_id = null)
{
    $sql = "
        SELECT o.*, 
               GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items,
               (o.total_amount + COALESCE(o.shipping_cost, 0)) as grand_total
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE o.id = ?
    ";
    $params = [$order_id];

    if ($user_id) {
        $sql .= " AND o.user_id = ?";
        $params[] = $user_id;
    }

    $sql .= " GROUP BY o.id";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function createOrder($conn, $user_id, $items, $shipping_address, $shipping_city, $shipping_postal_code, $payment_method, $notes = '')
{
    try {
        $conn->beginTransaction();

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shipping_cost = ($subtotal >= 100000) ? 0 : 15000;

        $grand_total = $subtotal + $shipping_cost;

        $order_number = 'ORD-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

        $columns = "user_id, order_number, total_amount, shipping_cost, shipping_address, shipping_city, shipping_postal_code, payment_method, notes, status, payment_status";
        $values = "?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending'";
        $params = [$user_id, $order_number, $subtotal, $shipping_cost, $shipping_address, $shipping_city, $shipping_postal_code, $payment_method, $notes];

        try {
            $stmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'orders' AND COLUMN_NAME = 'grand_total'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $columns .= ", grand_total";
                $values .= ", ?";
                $params[] = $grand_total;
            }
        } catch (Exception $e) {
        }

        $stmt = $conn->prepare("INSERT INTO orders ($columns) VALUES ($values)");
        $stmt->execute($params);

        $order_id = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, product_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($items as $item) {
            $item_subtotal = $item['price'] * $item['quantity'];
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price'], $item_subtotal]);
        }

        clearCart($conn, $user_id);

        $conn->commit();
        return $order_id;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

function createOrderWithStockUpdate($conn, $user_id, $items, $shipping_address, $shipping_city, $shipping_postal_code, $payment_method, $notes = '')
{
    try {
        $conn->beginTransaction();

        foreach ($items as $item) {
            $stmt = $conn->prepare("
                SELECT stock, sold, name,
                       (stock - COALESCE(sold, 0)) as available_stock 
                FROM products 
                WHERE id = ? AND status = 'active'
                FOR UPDATE
            ");
            $stmt->execute([$item['product_id']]);
            $product_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product_data) {
                throw new Exception("Produk tidak ditemukan: " . ($item['name'] ?? $item['product_id']));
            }

            $available_stock = max(0, intval($product_data['available_stock']));

            if ($item['quantity'] > $available_stock) {
                throw new Exception("Stok tidak mencukupi untuk {$product_data['name']}. Tersedia: {$available_stock}, diminta: {$item['quantity']}");
            }
        }

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $shipping_cost = ($subtotal >= 100000) ? 0 : 15000;
        $grand_total = $subtotal + $shipping_cost;

        $order_number = 'VEG-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $columns = "user_id, order_number, total_amount, shipping_cost, shipping_address, shipping_city, shipping_postal_code, payment_method, notes, status, payment_status, created_at";
        $values = "?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW()";
        $params = [$user_id, $order_number, $subtotal, $shipping_cost, $shipping_address, $shipping_city, $shipping_postal_code, $payment_method, $notes];

        try {
            $stmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'orders' AND COLUMN_NAME = 'grand_total' LIMIT 1");
            $stmt->execute();
            if ($stmt->fetch()) {
                $columns .= ", grand_total";
                $values .= ", ?";
                $params[] = $grand_total;
            }
        } catch (Exception $e) {
        }

        $stmt = $conn->prepare("INSERT INTO orders ($columns) VALUES ($values)");
        $stmt->execute($params);

        $order_id = $conn->lastInsertId();

        $stmt_order_item = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt_update_stock = $conn->prepare("
            UPDATE products 
            SET sold = sold + ?, updated_at = NOW() 
            WHERE id = ?
        ");

        foreach ($items as $item) {
            $stmt_order_item->execute([
                $order_id,
                $item['product_id'],
                $item['quantity'],
                $item['price']
            ]);

            $stmt_update_stock->execute([
                $item['quantity'],
                $item['product_id']
            ]);
        }

        clearCart($conn, $user_id);

        $conn->commit();

        return $order_id;
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        throw $e;
    }
}

function getAllOrders($conn, $status = '', $page = 1, $limit = 20)
{
    $offset = ($page - 1) * $limit;
    $conditions = [];
    $params = [];

    if ($status) {
        $conditions[] = "status = ?";
        $params[] = $status;
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

    $sql = "
        SELECT o.*, u.full_name, u.email,
               COUNT(oi.id) as item_count,
               (o.total_amount + COALESCE(o.shipping_cost, 0)) as grand_total
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $where
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT " . intval($limit) . " OFFSET " . intval($offset);

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function updateOrderStatus($conn, $order_id, $status, $admin_id)
{
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = ?, updated_at = NOW() 
        WHERE id = ?
    ");

    $result = $stmt->execute([$status, $order_id]);

    if ($result) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO order_status_history (order_id, status, changed_by, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$order_id, $status, $admin_id]);
        } catch (Exception $e) {
        }
    }

    return $result;
}

function getDashboardStats($conn)
{
    $stats = [];

    $stmt = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $stats['orders_today'] = $stmt->fetch()['count'];

    $stmt = $conn->query("SELECT SUM(total_amount + COALESCE(shipping_cost, 0)) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'");
    $stats['revenue_today'] = $stmt->fetch()['total'] ?? 0;

    $stmt = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = $stmt->fetch()['count'];

    $stmt = $conn->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $stats['total_products'] = $stmt->fetch()['count'];

    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_customers'] = $stmt->fetch()['count'];

    return $stats;
}
