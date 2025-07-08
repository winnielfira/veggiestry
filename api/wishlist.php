<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../config/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'add':
            addToWishlist($conn, $user_id);
            break;

        case 'remove':
            removeFromWishlist($conn, $user_id);
            break;

        case 'toggle':
            toggleWishlist($conn, $user_id);
            break;

        case 'getAll':
            getWishlistItems($conn, $user_id);
            break;

        case 'getCount':
            getWishlistCount($conn, $user_id);
            break;

        case 'check':
            checkInWishlist($conn, $user_id);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Wishlist API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function addToWishlist($conn, $user_id)
{
    $product_id = $_POST['product_id'] ?? 0;

    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Product already in wishlist']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    if ($stmt->execute([$user_id, $product_id])) {
        echo json_encode(['success' => true, 'message' => 'Product added to wishlist']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add to wishlist']);
    }
}

function removeFromWishlist($conn, $user_id)
{
    $product_id = $_POST['product_id'] ?? 0;

    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    if ($stmt->execute([$user_id, $product_id])) {
        echo json_encode(['success' => true, 'message' => 'Product removed from wishlist']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to remove from wishlist']);
    }
}

function toggleWishlist($conn, $user_id)
{
    $product_id = $_POST['product_id'] ?? 0;

    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);

    if ($stmt->fetch()) {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist']);
    } else {
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to wishlist']);
    }
}

function getWishlistItems($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT w.*, p.name, p.price, p.rating, p.sold, p.slug,
               c.name as category_name
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE w.user_id = ? AND p.status = 'active'
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        if (!empty($item['slug'])) {
            $item['image'] = $item['slug'] . '.jpg';
        } else {
            $clean_name = strtolower(str_replace([' ', '&', ','], ['-', 'dan', ''], $item['name']));
            $item['image'] = $clean_name . '.jpg';
        }
    }

    echo json_encode(['success' => true, 'items' => $items]);
}

function getWishlistCount($conn, $user_id)
{
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ? AND p.status = 'active'
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();

    echo json_encode(['success' => true, 'count' => $result['count']]);
}

function checkInWishlist($conn, $user_id)
{
    $product_id = $_GET['product_id'] ?? 0;

    if (!$product_id) {
        echo json_encode(['success' => false, 'error' => 'Product ID is required']);
        return;
    }

    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $exists = $stmt->fetch();

    echo json_encode(['success' => true, 'inWishlist' => $exists ? true : false]);
}
