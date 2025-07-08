<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields (name)']);
    exit();
}

$name = trim($input['name']);
$description = trim($input['description'] ?? '');
$slug = strtolower(str_replace(' ', '-', $name));

if (empty($name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Category name is required']);
    exit();
}

try {
    $check_query = "SELECT id FROM categories WHERE name = ? OR slug = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([$name, $slug]);

    if ($check_stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Category with this name already exists']);
        exit();
    }

    $insert_query = "INSERT INTO categories (name, slug, description, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_query);
    $result = $stmt->execute([$name, $slug, $description]);

    if (!$result) {
        throw new Exception("Failed to insert category");
    }

    $category_id = $conn->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Kategori "' . $name . '" berhasil ditambahkan',
        'category_id' => $category_id
    ]);
} catch (PDOException $e) {
    error_log("Add category error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Add category error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
