<?php
session_start();
include '../config/database.php';
include '../includes/functions.php';

header('Content-Type: application/json');

error_log("Get cart count API called");
error_log("Session: " . print_r($_SESSION, true));

if (!isLoggedIn()) {
    error_log("User not logged in for cart count");
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $cart_items = getCartItems($conn, $user_id);
    $count = 0;

    foreach ($cart_items as $item) {
        $count += $item['quantity'];
    }

    error_log("Cart count for user $user_id: $count");
    error_log("Cart items: " . print_r($cart_items, true));

    echo json_encode(['success' => true, 'count' => $count]);
} catch (Exception $e) {
    error_log("Error getting cart count: " . $e->getMessage());
    echo json_encode(['success' => false, 'count' => 0, 'message' => $e->getMessage()]);
}
