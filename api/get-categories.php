<?php
session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

error_log("Categories API called - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    error_log("Authentication failed - Session: " . print_r($_SESSION, true));
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../config/database.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    error_log("Processing action: $action with method: $method");

    switch ($method) {
        case 'GET':
            handleGet($conn, $action);
            break;

        case 'POST':
            handlePost($conn, $action);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Categories API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function handleGet($conn, $action)
{
    switch ($action) {
        case 'getAll':
            getAllCategories($conn);
            break;

        case 'getById':
            getCategoryById($conn);
            break;

        default:
            getAllCategories($conn);
            break;
    }
}

function handlePost($conn, $action)
{
    switch ($action) {
        case 'create':
            createCategory($conn);
            break;

        case 'update':
            updateCategory($conn);
            break;

        case 'delete':
            deleteCategory($conn);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
}

function getAllCategories($conn)
{
    try {
        $query = "
            SELECT c.*, 
                   COUNT(p.id) as product_count
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
            GROUP BY c.id
            ORDER BY c.name ASC
        ";

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("Found " . count($categories) . " categories");

        echo json_encode($categories);
    } catch (PDOException $e) {
        error_log("Get categories error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getCategoryById($conn)
{
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
        return;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Category not found']);
            return;
        }

        echo json_encode(['success' => true, 'data' => $category]);
    } catch (PDOException $e) {
        error_log("Get category by ID error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
}

function createCategory($conn)
{
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    error_log("Creating category - Name: $name, Description: $description");

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nama kategori harus diisi']);
        return;
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

    try {
        $checkStmt = $conn->prepare("SELECT id FROM categories WHERE name = ? OR slug = ?");
        $checkStmt->execute([$name, $slug]);

        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Kategori dengan nama ini sudah ada']);
            return;
        }

        $insertStmt = $conn->prepare("
            INSERT INTO categories (name, slug, description, created_at) 
            VALUES (?, ?, ?, NOW())
        ");

        $result = $insertStmt->execute([$name, $slug, $description]);

        if ($result) {
            $categoryId = $conn->lastInsertId();
            error_log("Category created successfully with ID: $categoryId");

            echo json_encode([
                'success' => true,
                'message' => "Kategori '$name' berhasil ditambahkan",
                'data' => [
                    'id' => $categoryId,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description
                ]
            ]);
        } else {
            throw new Exception("Failed to insert category");
        }
    } catch (PDOException $e) {
        error_log("Create category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateCategory($conn)
{
    error_log("=== UPDATE CATEGORY START ===");
    error_log("POST data: " . print_r($_POST, true));

    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    error_log("Updating category ID: $id - Name: '$name' - Description: '$description'");

    if ($id <= 0) {
        error_log("ERROR: Invalid category ID: $id");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
        return;
    }

    if (empty($name)) {
        error_log("ERROR: Empty category name");
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nama kategori harus diisi']);
        return;
    }

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    error_log("Generated slug: '$slug'");

    try {
        error_log("Checking if category exists...");
        $checkStmt = $conn->prepare("SELECT id, name FROM categories WHERE id = ?");
        $checkStmt->execute([$id]);
        $existingCategory = $checkStmt->fetch();

        if (!$existingCategory) {
            error_log("ERROR: Category not found for ID: $id");
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Kategori tidak ditemukan']);
            return;
        }

        error_log("Found existing category: " . print_r($existingCategory, true));

        error_log("Checking for duplicate name/slug...");
        $duplicateStmt = $conn->prepare("SELECT id, name FROM categories WHERE (name = ? OR slug = ?) AND id != ?");
        $duplicateStmt->execute([$name, $slug, $id]);
        $duplicate = $duplicateStmt->fetch();

        if ($duplicate) {
            error_log("ERROR: Duplicate category found: " . print_r($duplicate, true));
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Kategori dengan nama ini sudah ada']);
            return;
        }

        error_log("Checking table structure...");
        $columnCheckStmt = $conn->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'categories' AND COLUMN_NAME = 'updated_at' AND TABLE_SCHEMA = DATABASE()");
        $columnCheckStmt->execute();
        $hasUpdatedAt = $columnCheckStmt->fetch();

        if ($hasUpdatedAt) {
            error_log("Table has updated_at column - using it");
            $updateQuery = "UPDATE categories SET name = ?, slug = ?, description = ?, updated_at = NOW() WHERE id = ?";
        } else {
            error_log("Table does NOT have updated_at column - skipping it");
            $updateQuery = "UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?";
        }

        error_log("Update query: $updateQuery");

        $updateStmt = $conn->prepare($updateQuery);
        $result = $updateStmt->execute([$name, $slug, $description, $id]);

        if ($result) {
            $affectedRows = $updateStmt->rowCount();
            error_log("Category updated successfully - Affected rows: $affectedRows");

            echo json_encode([
                'success' => true,
                'message' => "Kategori '$name' berhasil diperbarui"
            ]);
        } else {
            error_log("ERROR: Update query failed");
            $errorInfo = $updateStmt->errorInfo();
            error_log("SQL Error Info: " . print_r($errorInfo, true));
            throw new Exception("Failed to update category - SQL Error: " . $errorInfo[2]);
        }
    } catch (PDOException $e) {
        error_log("PDO ERROR in updateCategory: " . $e->getMessage());
        error_log("SQL State: " . $e->getCode());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("GENERAL ERROR in updateCategory: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    error_log("=== UPDATE CATEGORY END ===");
}

function deleteCategory($conn)
{
    $id = intval($_POST['id'] ?? 0);

    error_log("Deleting category ID: $id");

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
        return;
    }

    try {
        $checkStmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
        $checkStmt->execute([$id]);
        $category = $checkStmt->fetch();

        if (!$category) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Kategori tidak ditemukan']);
            return;
        }

        $productStmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND status = 'active'");
        $productStmt->execute([$id]);
        $productCount = $productStmt->fetch()['count'];

        if ($productCount > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => "Kategori tidak dapat dihapus karena masih memiliki $productCount produk aktif"]);
            return;
        }

        $deleteStmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $result = $deleteStmt->execute([$id]);

        if ($result) {
            error_log("Category deleted successfully");

            echo json_encode([
                'success' => true,
                'message' => "Kategori '{$category['name']}' berhasil dihapus"
            ]);
        } else {
            throw new Exception("Failed to delete category");
        }
    } catch (PDOException $e) {
        error_log("Delete category error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}
