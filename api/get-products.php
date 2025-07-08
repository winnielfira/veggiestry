<?php
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$writeActions = ['create', 'update', 'delete'];

if (in_array($action, $writeActions)) {
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
        exit();
    }
}

include '../config/database.php';

try {
    switch ($action) {
        case 'getAll':
            getAllProducts($conn);
            break;

        case 'get':
            getProduct($conn);
            break;

        case 'create':
            createProduct($conn);
            break;

        case 'update':
            updateProduct($conn);
            break;

        case 'delete':
            deleteProduct($conn);
            break;

        default:
            getAllProducts($conn);
            break;
    }
} catch (Exception $e) {
    error_log("Products API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function getAllProducts($conn)
{
    try {
        $query = "SELECT p.id, p.name, p.description, p.price, p.stock, p.sold, p.slug,
                         (p.stock - COALESCE(p.sold, 0)) as available_stock,
                         c.name as category, p.status, p.created_at, p.updated_at,
                         COALESCE(AVG(r.rating), 0) as avg_rating,
                         COUNT(DISTINCT r.id) as review_count
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id
                  LEFT JOIN reviews r ON p.id = r.product_id
                  WHERE p.status = 'active'
                  GROUP BY p.id, p.name, p.description, p.price, p.stock, p.sold, p.slug,
                           c.name, p.status, p.created_at, p.updated_at
                  ORDER BY p.created_at DESC";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as &$product) {
            $product['avg_rating'] = round(floatval($product['avg_rating']), 1);
            $product['total_sold'] = intval($product['sold']);
            $product['review_count'] = intval($product['review_count']);
            $product['is_active'] = ($product['status'] === 'active') ? 1 : 0;

            $product['available_stock'] = max(0, intval($product['available_stock']));
            $product['is_out_of_stock'] = ($product['available_stock'] <= 0);
            $product['is_low_stock'] = ($product['available_stock'] > 0 && $product['available_stock'] <= 5);

            if (!empty($product['slug'])) {
                $product['image_url'] = $product['slug'] . '.jpg';
            } else {
                $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $product['name']));
                $product['image_url'] = $clean_name . '.jpg';
            }
        }

        echo json_encode($products);
    } catch (Exception $e) {
        throw $e;
    }
}

function getProduct($conn)
{
    $id = $_GET['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }

    try {
        $query = "SELECT p.id, p.name, p.description, p.price, p.stock, p.sold, p.slug,
                         (p.stock - COALESCE(p.sold, 0)) as available_stock,
                         c.name as category, p.status, p.created_at, p.updated_at 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = ?";

        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $product['is_active'] = ($product['status'] === 'active') ? 1 : 0;
            $product['available_stock'] = max(0, intval($product['available_stock']));
            $product['is_out_of_stock'] = ($product['available_stock'] <= 0);
            $product['is_low_stock'] = ($product['available_stock'] > 0 && $product['available_stock'] <= 5);

            if (!empty($product['slug'])) {
                $product['image_url'] = $product['slug'] . '.jpg';
            } else {
                $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $product['name']));
                $product['image_url'] = $clean_name . '.jpg';
            }

            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Product not found']);
        }
    } catch (Exception $e) {
        throw $e;
    }
}

function createProduct($conn)
{
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category = trim($_POST['category'] ?? '');

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Nama produk harus diisi']);
        return;
    }

    if ($price <= 0) {
        echo json_encode(['success' => false, 'error' => 'Harga produk harus lebih dari 0']);
        return;
    }

    if ($stock < 0) {
        echo json_encode(['success' => false, 'error' => 'Stok tidak boleh negatif']);
        return;
    }

    try {
        $category_id = null;
        if (!empty($category)) {
            $catQuery = "SELECT id FROM categories WHERE name = ?";
            $catStmt = $conn->prepare($catQuery);
            $catStmt->execute([$category]);
            $catResult = $catStmt->fetch();
            if ($catResult) {
                $category_id = $catResult['id'];
            } else {
                $createCatQuery = "INSERT INTO categories (name, slug) VALUES (?, ?)";
                $createCatStmt = $conn->prepare($createCatQuery);
                $slug = strtolower(str_replace(' ', '-', $category));
                $createCatStmt->execute([$category, $slug]);
                $category_id = $conn->lastInsertId();
            }
        }

        $checkQuery = "SELECT id FROM products WHERE name = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$name]);

        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Nama produk sudah ada']);
            return;
        }

        $slug = strtolower(str_replace(' ', '-', $name));

        $query = "INSERT INTO products (category_id, name, slug, description, price, stock, status, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";

        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$category_id, $name, $slug, $description, $price, $stock]);

        if ($result) {
            $productId = $conn->lastInsertId();
            echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan', 'product_id' => $productId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal menambahkan produk']);
        }
    } catch (Exception $e) {
        throw $e;
    }
}

function updateProduct($conn)
{
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category = trim($_POST['category'] ?? '');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Product ID tidak valid']);
        return;
    }

    if (empty($name)) {
        echo json_encode(['success' => false, 'error' => 'Nama produk harus diisi']);
        return;
    }

    if ($price <= 0) {
        echo json_encode(['success' => false, 'error' => 'Harga produk harus lebih dari 0']);
        return;
    }

    if ($stock < 0) {
        echo json_encode(['success' => false, 'error' => 'Stok tidak boleh negatif']);
        return;
    }

    try {
        $checkQuery = "SELECT id FROM products WHERE id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$id]);

        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Produk tidak ditemukan']);
            return;
        }

        $category_id = null;
        if (!empty($category)) {
            $catQuery = "SELECT id FROM categories WHERE name = ?";
            $catStmt = $conn->prepare($catQuery);
            $catStmt->execute([$category]);
            $catResult = $catStmt->fetch();
            if ($catResult) {
                $category_id = $catResult['id'];
            }
        }

        $nameCheckQuery = "SELECT id FROM products WHERE name = ? AND id != ?";
        $nameCheckStmt = $conn->prepare($nameCheckQuery);
        $nameCheckStmt->execute([$name, $id]);

        if ($nameCheckStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Nama produk sudah ada']);
            return;
        }

        $query = "UPDATE products 
                  SET category_id = ?, name = ?, description = ?, price = ?, stock = ?, updated_at = NOW() 
                  WHERE id = ?";

        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$category_id, $name, $description, $price, $stock, $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Produk berhasil diupdate']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal mengupdate produk']);
        }
    } catch (Exception $e) {
        throw $e;
    }
}

function deleteProduct($conn)
{
    $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Product ID tidak valid']);
        return;
    }

    try {
        $checkQuery = "SELECT id FROM products WHERE id = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$id]);

        if (!$checkStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Produk tidak ditemukan']);
            return;
        }

        $orderCheckQuery = "SHOW TABLES LIKE 'order_items'";
        $orderTableExists = $conn->query($orderCheckQuery)->fetch();

        if ($orderTableExists) {
            $orderCheckQuery = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
            $orderCheckStmt = $conn->prepare($orderCheckQuery);
            $orderCheckStmt->execute([$id]);
            $orderCount = $orderCheckStmt->fetch()['count'];

            if ($orderCount > 0) {
                $deactivateQuery = "UPDATE products SET status = 'inactive', updated_at = NOW() WHERE id = ?";
                $deactivateStmt = $conn->prepare($deactivateQuery);
                $result = $deactivateStmt->execute([$id]);

                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Produk dinonaktifkan karena sudah ada dalam pesanan']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Gagal menonaktifkan produk']);
                }
                return;
            }
        }

        $deleteQuery = "DELETE FROM products WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $result = $deleteStmt->execute([$id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal menghapus produk']);
        }
    } catch (Exception $e) {
        throw $e;
    }
}
