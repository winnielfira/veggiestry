<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$period = $_GET['period'] ?? 'today';

include '../database.php';

try {
    $dateCondition = '';
    switch ($period) {
        case 'today':
            $dateCondition = "DATE(o.created_at) = CURDATE()";
            break;
        case 'week':
            $dateCondition = "WEEK(o.created_at) = WEEK(NOW()) AND YEAR(o.created_at) = YEAR(NOW())";
            break;
        case 'month':
            $dateCondition = "MONTH(o.created_at) = MONTH(NOW()) AND YEAR(o.created_at) = YEAR(NOW())";
            break;
        case 'year':
            $dateCondition = "YEAR(o.created_at) = YEAR(NOW())";
            break;
        default:
            $dateCondition = "1=1";
    }

    $statsQuery = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    COUNT(CASE WHEN status = 'delivered' THEN 1 END) as completed_orders
                   FROM orders o 
                   WHERE $dateCondition AND status != 'cancelled'";

    $stmt = $conn->prepare($statsQuery);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $topProductsQuery = "SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue
                         FROM order_items oi
                         JOIN products p ON oi.product_id = p.id
                         JOIN orders o ON oi.order_id = o.id
                         WHERE $dateCondition AND o.status != 'cancelled'
                         GROUP BY p.id, p.name
                         ORDER BY total_sold DESC
                         LIMIT 5";

    $stmt = $conn->prepare($topProductsQuery);
    $stmt->execute();
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $reports = [
        'total_orders' => $stats['total_orders'] ?? 0,
        'total_revenue' => $stats['total_revenue'] ?? 0,
        'completed_orders' => $stats['completed_orders'] ?? 0,
        'top_products' => $topProducts,
        'period' => $period
    ];

    echo json_encode($reports);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
