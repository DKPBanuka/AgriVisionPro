<?php
require_once 'db_connect.php';
header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Handle different request methods
    switch ($method) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
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
    
    if (!isset($_GET['item_id'])) {
        throw new Exception('Missing item ID', 400);
    }
    
    $itemId = (int)$_GET['item_id'];
    
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name as user_name 
        FROM activities a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE item_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$itemId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($activities);
}

function handlePostRequest() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate data
    if (empty($data['type']) || empty($data['description'])) {
        throw new Exception('Missing required fields', 400);
    }
    
    // Insert new activity
    $stmt = $pdo->prepare("
        INSERT INTO activities 
        (type, description, item_id, user_id, user_name) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $data['type'],
        $data['description'],
        $data['item_id'] ?? null,
        $data['user_id'] ?? null,
        $data['user_name'] ?? null
    ]);
    
    if (!$success) {
        throw new Exception('Failed to create activity', 500);
    }
    
    $activityId = $pdo->lastInsertId();
    
    // Return created activity
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->execute([$activityId]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(201);
    echo json_encode($activity);
}