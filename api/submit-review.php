<?php
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../config/database.php';

$action = $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

error_log("Submit Review API called - User ID: $user_id, Action: $action");

try {
    switch ($action) {
        case 'submit_all':
            submitRatingForAllProducts($conn, $user_id);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    error_log("Submit Review API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function submitRatingForAllProducts($conn, $user_id)
{
    $order_id = $_POST['order_id'] ?? 0;
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    error_log("Submit Rating for All Products - Order: $order_id, Rating: $rating, User: $user_id");

    if (!$order_id) {
        error_log("Validation failed - Missing order_id");
        echo json_encode(['success' => false, 'error' => 'Order ID is required']);
        return;
    }

    if ($rating < 1 || $rating > 5) {
        error_log("Validation failed - Invalid rating: $rating");
        echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5']);
        return;
    }

    try {
        $conn->beginTransaction();
        error_log("Database transaction started");

        $stmt = $conn->prepare("
            SELECT id, status, COALESCE(user_confirmed, FALSE) as user_confirmed
            FROM orders 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();

        error_log("Order query result: " . json_encode($order));

        if (!$order) {
            error_log("Order not found - Order ID: $order_id, User ID: $user_id");
            throw new Exception('Order not found');
        }

        if (!$order['user_confirmed']) {
            error_log("Order not confirmed - Order: $order_id");
            throw new Exception('Order must be confirmed complete before reviewing');
        }

        $stmt = $conn->prepare("
            SELECT DISTINCT oi.product_id, p.name as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($order_products)) {
            throw new Exception('No products found in this order');
        }

        error_log("Found " . count($order_products) . " products in order");

        $stmt = $conn->prepare("
            SELECT COUNT(*) as review_count
            FROM reviews 
            WHERE user_id = ? AND order_id = ?
        ");
        $stmt->execute([$user_id, $order_id]);
        $existing_reviews = $stmt->fetch();

        if ($existing_reviews['review_count'] > 0) {
            error_log("Reviews already exist for this order");
            echo json_encode([
                'success' => false,
                'error' => 'Rating sudah diberikan untuk pesanan ini',
                'error_code' => 'REVIEWS_ALREADY_EXIST'
            ]);
            $conn->rollback();
            return;
        }

        $successful_reviews = [];
        $failed_reviews = [];

        foreach ($order_products as $product) {
            $product_id = $product['product_id'];
            $product_name = $product['product_name'];

            error_log("Creating review for product $product_id ($product_name) with rating $rating");

            try {
                $stmt = $conn->prepare("
                    INSERT INTO reviews (user_id, product_id, order_id, rating, comment, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");

                $insertResult = $stmt->execute([$user_id, $product_id, $order_id, $rating, $comment]);

                if ($insertResult) {
                    $review_id = $conn->lastInsertId();
                    error_log("Review inserted successfully - ID: $review_id for product $product_id");

                    $newRating = updateProductRating($conn, $product_id);

                    $successful_reviews[] = [
                        'review_id' => $review_id,
                        'product_id' => $product_id,
                        'product_name' => $product_name,
                        'rating' => $rating,
                        'new_product_rating' => $newRating['rating'],
                        'total_reviews' => $newRating['review_count']
                    ];
                } else {
                    $failed_reviews[] = "Failed to save review for product $product_id ($product_name)";
                }
            } catch (Exception $e) {
                error_log("Error creating review for product $product_id: " . $e->getMessage());
                $failed_reviews[] = "Error for product $product_name: " . $e->getMessage();
            }
        }

        if (empty($successful_reviews)) {
            throw new Exception('No reviews were saved successfully. Errors: ' . implode('; ', $failed_reviews));
        }

        $conn->commit();
        error_log("Database transaction committed successfully");

        $total_products = count($order_products);
        $successful_count = count($successful_reviews);

        $message = $successful_count === $total_products
            ? "Rating $rating bintang berhasil diberikan untuk semua $total_products produk! Terima kasih atas feedback Anda."
            : "Rating berhasil diberikan untuk $successful_count dari $total_products produk.";

        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => [
                'order_id' => $order_id,
                'rating' => $rating,
                'comment' => $comment,
                'total_products' => $total_products,
                'successful_reviews' => $successful_count,
                'failed_reviews' => count($failed_reviews),
                'products' => $successful_reviews
            ],
            'warnings' => !empty($failed_reviews) ? $failed_reviews : null
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Submit rating for all products error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function updateProductRating($conn, $product_id)
{
    error_log("Updating product rating for product ID: $product_id");

    $stmt = $conn->prepare("
        SELECT 
            AVG(rating) as avg_rating,
            COUNT(*) as review_count
        FROM reviews 
        WHERE product_id = ?
    ");
    $stmt->execute([$product_id]);
    $result = $stmt->fetch();

    $new_rating = round($result['avg_rating'], 1);
    $review_count = $result['review_count'];

    error_log("Calculated new rating: $new_rating, Review count: $review_count");

    $stmt = $conn->prepare("
        UPDATE products 
        SET rating = ?, review_count = ?, updated_at = NOW() 
        WHERE id = ?
    ");

    $updateResult = $stmt->execute([$new_rating, $review_count, $product_id]);
    error_log("Product update result: " . ($updateResult ? 'SUCCESS' : 'FAILED'));

    if (!$updateResult) {
        throw new Exception('Failed to update product rating');
    }

    return ['rating' => $new_rating, 'review_count' => $review_count];
}
