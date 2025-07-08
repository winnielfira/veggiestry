<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    include '../config/database.php';
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

try {
    $check_table = $conn->query("SHOW TABLES LIKE 'orders'");
    if (!$check_table->fetch()) {
        echo json_encode([
            'success' => true,
            'total_orders' => 0,
            'total_revenue' => 0,
            'pending_orders' => 0,
            'today_orders' => 0,
            'total_customers' => 0,
            'total_products' => 0,
            'low_stock_products' => 0,
            'message' => 'Database tables not found - using default values'
        ]);
        exit();
    }

    $stats = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'pending_orders' => 0,
        'today_orders' => 0,
        'total_customers' => 0,
        'total_products' => 0,
        'low_stock_products' => 0
    ];

    try {
        $result = $conn->query("SELECT COUNT(*) as total FROM orders");
        $stats['total_orders'] = $result->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting total orders: " . $e->getMessage());
    }

    try {
        $result = $conn->query("SELECT SUM(total_amount) as revenue FROM orders WHERE status != 'cancelled'");
        $stats['total_revenue'] = $result->fetch()['revenue'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting total revenue: " . $e->getMessage());
    }

    try {
        $result = $conn->query("SELECT COUNT(*) as pending FROM orders WHERE status = 'pending'");
        $stats['pending_orders'] = $result->fetch()['pending'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting pending orders: " . $e->getMessage());
    }

    try {
        $result = $conn->query("SELECT COUNT(*) as today FROM orders WHERE DATE(created_at) = CURDATE()");
        $stats['today_orders'] = $result->fetch()['today'] ?? 0;
    } catch (Exception $e) {
        error_log("Error getting today orders: " . $e->getMessage());
    }

    try {
        $check_users = $conn->query("SHOW TABLES LIKE 'users'");
        if ($check_users->fetch()) {
            $result = $conn->query("SELECT COUNT(*) as total FROM users");
            $stats['total_customers'] = $result->fetch()['total'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Error getting total customers: " . $e->getMessage());
    }

    try {
        $check_products = $conn->query("SHOW TABLES LIKE 'products'");
        if ($check_products->fetch()) {
            $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
            $stats['total_products'] = $result->fetch()['total'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Error getting total products: " . $e->getMessage());
    }

    try {
        $check_products = $conn->query("SHOW TABLES LIKE 'products'");
        if ($check_products->fetch()) {
            $result = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock <= 5 AND status = 'active'");
            $stats['low_stock_products'] = $result->fetch()['total'] ?? 0;
        }
    } catch (Exception $e) {
        error_log("Error getting low stock products: " . $e->getMessage());
    }

    $response = array_merge($stats, [
        'success' => true,
        'last_updated' => date('Y-m-d H:i:s')
    ]);

    echo json_encode($response);
} catch (PDOException $e) {
    error_log("Dashboard stats PDO error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Dashboard stats general error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
