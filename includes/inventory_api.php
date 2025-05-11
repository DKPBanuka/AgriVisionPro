<?php
require_once 'includes/db_connect.php';
header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle different request methods
    switch ($method) {
        case 'GET':
            // Handle GET requests (listing items)
            $items = []; // Fetch from database
            echo json_encode(['items' => $items, 'total' => count($items)]);
            break;
        case 'POST':
            echo json_encode(['success' => true, 'id' => $newId]);
            break;
        case 'PUT':
            handlePutRequest();
            break;
        case 'DELETE':
            handleDeleteRequest();
            break;
        default:
            throw new Exception('Method not allowed', 405);
    }
} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetRequest() {
    global $pdo;
    
    // Check if we're getting stats
    if (isset($_GET['stats'])) {
        $statsType = $_GET['stats'];
        
        if ($statsType === 'summary') {
            // Get summary statistics
            $stmt = $pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'in-stock' THEN 1 ELSE 0 END) as in_stock,
                    SUM(CASE WHEN status = 'low-stock' THEN 1 ELSE 0 END) as low_stock,
                    SUM(CASE WHEN status = 'out-of-stock' THEN 1 ELSE 0 END) as out_of_stock
                FROM inventory
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode($stats);
            return;
        } elseif ($statsType === 'category') {
            // Get category distribution
            $stmt = $pdo->query("
                SELECT category, COUNT(*) as count 
                FROM inventory 
                GROUP BY category
            ");
            $categories = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            echo json_encode($categories);
            return;
        } elseif (isset($_GET['count'])) {
            // Get total count
            $stmt = $pdo->query("SELECT COUNT(*) FROM inventory");
            $count = $stmt->fetchColumn();
            
            echo json_encode(['total' => $count]);
            return;
        }
    }
    
    // Get single item
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$item) {
            throw new Exception('Item not found', 404);
        }
        
        echo json_encode($item);
        return;
    }
    
    // Search items
    if (isset($_GET['search'])) {
        $searchTerm = '%' . $_GET['search'] . '%';
        $stmt = $pdo->prepare("
            SELECT id, name, category, status 
            FROM inventory 
            WHERE name LIKE ? OR category LIKE ? OR location LIKE ?
            LIMIT 5
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($results);
        return;
    }
    
    // Get paginated items with filters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
    $offset = ($page - 1) * $perPage;
    
    $categoryFilter = isset($_GET['category']) && $_GET['category'] !== 'all' ? $_GET['category'] : null;
    $statusFilter = isset($_GET['status']) && $_GET['status'] !== 'all' ? $_GET['status'] : null;
    $searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : null;
    
    // Build query
    $query = "SELECT * FROM inventory";
    $conditions = [];
    $params = [];
    
    if ($categoryFilter) {
        $conditions[] = "category = ?";
        $params[] = $categoryFilter;
    }
    
    if ($statusFilter) {
        $conditions[] = "status = ?";
        $params[] = $statusFilter;
    }
    
    if ($searchTerm) {
        $conditions[] = "(name LIKE ? OR category LIKE ? OR location LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Add sorting
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name-asc';
    list($sortField, $sortOrder) = explode('-', $sort);
    
    $validSortFields = ['name', 'quantity', 'category'];
    $sortField = in_array($sortField, $validSortFields) ? $sortField : 'name';
    $sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';
    
    $query .= " ORDER BY $sortField $sortOrder";
    
    // Add pagination
    $query .= " LIMIT $offset, $perPage";
    
    // Get items
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM inventory";
    if (!empty($conditions)) {
        $countQuery .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage
    ]);
}

function handlePostRequest() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate data
    if (empty($data['name']) || !isset($data['quantity']) || empty($data['category']) || empty($data['unit'])) {
        throw new Exception('Missing required fields', 400);
    }
    
    // Calculate status based on quantity
    $status = calculateStatus($data['quantity']);
    
    // Insert new item
    $stmt = $pdo->prepare("
        INSERT INTO inventory 
        (name, category, quantity, unit, location, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $data['name'],
        $data['category'],
        $data['quantity'],
        $data['unit'],
        $data['location'] ?? null,
        $data['notes'] ?? null,
        $status
    ]);
    
    if (!$success) {
        throw new Exception('Failed to create item', 500);
    }
    
    $itemId = $pdo->lastInsertId();
    
    // Return created item
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(201);
    echo json_encode($item);
}

function handlePutRequest() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate data
    if (empty($data['id']) || empty($data['name']) || !isset($data['quantity']) || empty($data['category']) || empty($data['unit'])) {
        throw new Exception('Missing required fields', 400);
    }
    
    // Calculate status based on quantity
    $status = calculateStatus($data['quantity']);
    
    // Update item
    $stmt = $pdo->prepare("
        UPDATE inventory SET
        name = ?,
        category = ?,
        quantity = ?,
        unit = ?,
        location = ?,
        notes = ?,
        status = ?
        WHERE id = ?
    ");
    
    $success = $stmt->execute([
        $data['name'],
        $data['category'],
        $data['quantity'],
        $data['unit'],
        $data['location'] ?? null,
        $data['notes'] ?? null,
        $status,
        $data['id']
    ]);
    
    if (!$success || $stmt->rowCount() === 0) {
        throw new Exception('Item not found or update failed', 404);
    }
    
    // Return updated item
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$data['id']]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode($item);
}

function handleDeleteRequest() {
    global $pdo;
    
    if (!isset($_GET['id'])) {
        throw new Exception('Missing item ID', 400);
    }
    
    $id = (int)$_GET['id'];
    
    // First get the item to return it in the response
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('Item not found', 404);
    }
    
    // Delete the item
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
    $success = $stmt->execute([$id]);
    
    if (!$success) {
        throw new Exception('Failed to delete item', 500);
    }
    
    echo json_encode($item);
}

function calculateStatus($quantity) {
    if ($quantity <= 0) return 'out-of-stock';
    if ($quantity < 10) return 'low-stock';
    return 'in-stock';
}