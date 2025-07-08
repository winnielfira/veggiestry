<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

include '../config/database.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'getAll':
            getAllCustomers($conn);
            break;

        case 'get':
            getCustomer($conn);
            break;

        case 'update':
            updateCustomer($conn);
            break;

        case 'delete':
            deleteCustomer($conn);
            break;

        default:
            getAllCustomers($conn);
            break;
    }
} catch (Exception $e) {
    error_log("Customers API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}

function getAllCustomers($conn)
{
    try {
        $columns = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $hasUserType = in_array('user_type', $columns);

        $whereClause = '';
        if ($hasUserType) {
            $whereClause = "WHERE u.user_type != 'admin' OR u.user_type IS NULL";
        } else {
            $whereClause = "WHERE 1=1";
        }

        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $hasOrders = in_array('orders', $tables);

        if ($hasOrders) {
            $query = "
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.full_name,
                    u.phone,
                    u.address,
                    u.created_at,
                    u.updated_at,
                    COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total_amount ELSE 0 END), 0) as total_spent,
                    COUNT(CASE WHEN o.status != 'cancelled' THEN o.id END) as total_orders,
                    MAX(o.created_at) as last_order_date
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id
                $whereClause
                GROUP BY u.id, u.username, u.email, u.full_name, u.phone, u.address, u.created_at, u.updated_at
                ORDER BY u.created_at DESC
            ";
        } else {
            $query = "
                SELECT 
                    u.id,
                    u.username,
                    u.email,
                    u.full_name,
                    u.phone,
                    u.address,
                    u.created_at,
                    u.updated_at,
                    0 as total_spent,
                    0 as total_orders,
                    NULL as last_order_date
                FROM users u
                $whereClause
                ORDER BY u.created_at DESC
            ";
        }

        $stmt = $conn->prepare($query);
        $stmt->execute();
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($customers as &$customer) {
            $customer['total_spent'] = floatval($customer['total_spent']);
            $customer['total_orders'] = intval($customer['total_orders']);
            $customer['created_at_formatted'] = date('d M Y', strtotime($customer['created_at']));
            $customer['last_order_formatted'] = $customer['last_order_date'] ?
                date('d M Y', strtotime($customer['last_order_date'])) : null;
        }

        echo json_encode($customers);
    } catch (Exception $e) {
        throw $e;
    }
}

function getCustomer($conn)
{
    $id = $_GET['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
        return;
    }

    try {
        $columns = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $hasUserType = in_array('user_type', $columns);

        $whereClause = "WHERE u.id = ?";
        if ($hasUserType) {
            $whereClause .= " AND (u.user_type != 'admin' OR u.user_type IS NULL)";
        }

        $query = "
            SELECT 
                u.id,
                u.username,
                u.email,
                u.full_name,
                u.phone,
                u.address,
                u.created_at,
                u.updated_at,
                0 as total_spent,
                0 as total_orders,
                NULL as last_order_date
            FROM users u
            $whereClause
        ";

        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            echo json_encode(['success' => false, 'error' => 'Customer not found']);
            return;
        }

        $customer['total_spent'] = floatval($customer['total_spent']);
        $customer['total_orders'] = intval($customer['total_orders']);
        $customer['created_at_formatted'] = date('d M Y H:i', strtotime($customer['created_at']));
        $customer['last_order_formatted'] = null;

        echo json_encode(['success' => true, 'customer' => $customer]);
    } catch (Exception $e) {
        throw $e;
    }
}

function updateCustomer($conn)
{
    $id = $_POST['id'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
        return;
    }

    if (empty($full_name)) {
        echo json_encode(['success' => false, 'error' => 'Full name is required']);
        return;
    }

    if (empty($email)) {
        echo json_encode(['success' => false, 'error' => 'Email is required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        return;
    }

    try {
        $columns = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $hasUserType = in_array('user_type', $columns);

        $whereClause = "WHERE id = ?";
        if ($hasUserType) {
            $whereClause .= " AND (user_type != 'admin' OR user_type IS NULL)";
        }

        $checkQuery = "SELECT id, email FROM users $whereClause";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([$id]);
        $existingCustomer = $checkStmt->fetch();

        if (!$existingCustomer) {
            echo json_encode(['success' => false, 'error' => 'Customer not found']);
            return;
        }

        if ($existingCustomer['email'] !== $email) {
            $emailCheckQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
            $emailCheckStmt = $conn->prepare($emailCheckQuery);
            $emailCheckStmt->execute([$email, $id]);

            if ($emailCheckStmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Email already exists for another customer']);
                return;
            }
        }

        $updateFields = "full_name = ?, email = ?, phone = ?, address = ?";
        if (in_array('updated_at', $columns)) {
            $updateFields .= ", updated_at = NOW()";
        }

        $updateQuery = "UPDATE users SET $updateFields WHERE id = ?";

        $updateStmt = $conn->prepare($updateQuery);
        $result = $updateStmt->execute([$full_name, $email, $phone, $address, $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update customer']);
        }
    } catch (Exception $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'error' => 'Email already exists']);
        } else {
            throw $e;
        }
    }
}

function deleteCustomer($conn)
{
    $id = $_POST['id'] ?? '';

    if (empty($id)) {
        echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
        return;
    }

    try {
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('orders', $tables)) {
            $orderCheckQuery = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
            $orderCheckStmt = $conn->prepare($orderCheckQuery);
            $orderCheckStmt->execute([$id]);
            $orderResult = $orderCheckStmt->fetch();

            if ($orderResult['order_count'] > 0) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Cannot delete customer with existing orders. Consider deactivating instead.'
                ]);
                return;
            }
        }

        $columns = $conn->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $hasUserType = in_array('user_type', $columns);

        $whereClause = "WHERE id = ?";
        if ($hasUserType) {
            $whereClause .= " AND (user_type != 'admin' OR user_type IS NULL)";
        }

        $deleteQuery = "DELETE FROM users $whereClause";
        $deleteStmt = $conn->prepare($deleteQuery);
        $result = $deleteStmt->execute([$id]);

        if ($result && $deleteStmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Customer not found or cannot be deleted']);
        }
    } catch (Exception $e) {
        throw $e;
    }
}
