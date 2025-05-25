<?php
// Enable detailed error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../logs/php_errors.log'); // Ensure this path is correct and writable
ini_set('display_errors', 0); // Do not display errors in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();

// Database connection
require_once 'includes/db_connect.php'; // Assumes db_connect.php establishes $pdo

// Authentication functions
require_once 'includes/auth_functions.php'; // Assumes auth_functions.php has checkAuthentication() or similar

/**
 * Saves a task attachment.
 *
 * @param array $file A single file entry from $_FILES (e.g., $_FILES['attachments'][0] or $_FILES['attachment_name'])
 * @param string $uploadDir The base directory for uploads.
 * @return array|false An associative array with file details on success, or false on failure.
 */
function saveTaskAttachment($file, $uploadDir = '../uploads/task_attachments/') {
    // Ensure the base upload directory exists and is writable
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0775, true)) { // Create recursively
            error_log("Failed to create upload directory: " . $uploadDir);
            return false;
        }
    }
    if (!is_writable($uploadDir)) {
        error_log("Upload directory is not writable: " . $uploadDir);
        return false;
    }

    // Check for upload errors
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive specified in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE; // Default if error code isn't set
        error_log("File upload error for {$file['name']}: " . ($errorMessages[$errorCode] ?? 'Unknown upload error. Code: ' . $errorCode));
        return false;
    }

    // Size check (e.g., 10MB)
    $maxSize = 10 * 1024 * 1024; 
    if ($file['size'] > $maxSize) {
        error_log("File too large: " . $file['name'] . " ({$file['size']} bytes)");
        return false;
    }

    // Type check using finfo for better security than relying on client-provided MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .doc, .docx
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xls, .xlsx
        'text/plain',
        'application/zip', 'application/x-rar-compressed', // Archives
    ];
    if (!in_array($mimeType, $allowedMimeTypes)) {
        error_log("Invalid file type: " . $mimeType . " for file " . $file['name']);
        return false;
    }

    $originalFileName = basename($file['name']);
    $extension = pathinfo($originalFileName, PATHINFO_EXTENSION);
    $safeExtension = strtolower($extension); 

    $uniqueFileName = md5(uniqid(rand(), true)) . ($safeExtension ? '.' . $safeExtension : '');
    
    $targetFilePath = $uploadDir . $uniqueFileName;
    $webPath = '/uploads/task_attachments/' . $uniqueFileName; 

    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return [
            'file_name' => $originalFileName,
            'file_path' => $webPath, 
            'file_type' => $mimeType
        ];
    }
    error_log("Failed to move uploaded file '{$file['name']}' to '{$targetFilePath}'");
    return false;
}

$ALLOWED_RELATED_ITEM_TYPES = ['crop', 'livestock', 'equipment', 'field', 'inventory']; // Define allowed types

// Set response header to JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];
$requestData = [];
$action = null;
$userId = $_SESSION['user_id'] ?? null;

if ($userId === null) {
    http_response_code(401); 
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $jsonInput = file_get_contents('php://input');
    $requestData = json_decode($jsonInput, true) ?: []; 
} else { 
    $requestData = $_POST;
}


$action = $requestData['action'] ?? $_GET['action'] ?? null; 

if (empty($action)) {
    http_response_code(400); 
    $response['message'] = 'Action not specified.';
    echo json_encode($response);
    exit;
}

if (isset($pdo)) { 
    $pdo->beginTransaction();
} else {
    http_response_code(500);
    error_log("PDO object not available in tasks_api.php");
    $response['message'] = 'Database connection error.';
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'list_tasks':
            $search = $requestData['search'] ?? '';
            $statusFilter = $requestData['status'] ?? 'all'; 
            $priorityFilter = $requestData['priority'] ?? 'all'; 
            $sortBy = $requestData['sortBy'] ?? 'createdAt-desc'; 

            $sql = "SELECT * FROM tasks WHERE user_id = :user_id";
            $params = [':user_id' => $userId];
            $whereClauses = [];

            if (!empty($search)) {
                $whereClauses[] = "(title LIKE :search OR description LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            if ($priorityFilter !== 'all') {
                $whereClauses[] = "priority = :priority";
                $params[':priority'] = $priorityFilter;
            }

            if ($statusFilter !== 'all' && $statusFilter !== 'overdue') {
                $whereClauses[] = "status = :status";
                $params[':status'] = $statusFilter;
            } elseif ($statusFilter === 'overdue') {
                $whereClauses[] = "(due_date < CURDATE() AND status != 'completed')";
            }
            
            if (!empty($whereClauses)) {
                $sql .= " AND " . implode(" AND ", $whereClauses);
            }

            $orderByMap = [
                'title-asc' => 'title ASC',
                'title-desc' => 'title DESC',
                'dueDate-asc' => 'due_date ASC',
                'dueDate-desc' => 'due_date DESC',
                'priority-asc' => "FIELD(priority, 'low', 'medium', 'high') ASC, priority ASC", 
                'priority-desc' => "FIELD(priority, 'high', 'medium', 'low') DESC, priority DESC",
                'status-asc' => 'status ASC',
                'status-desc' => 'status DESC',
                'createdAt-asc' => 'created_at ASC',
                'createdAt-desc' => 'created_at DESC',
                'updatedAt-asc' => 'updated_at ASC',
                'updatedAt-desc' => 'updated_at DESC',
            ];
            
            $orderByClause = $orderByMap[$sortBy] ?? 'created_at DESC';
            $sql .= " ORDER BY " . $orderByClause;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($statusFilter !== 'overdue') { 
                $todayStr = date('Y-m-d'); 
                foreach ($tasks as &$task) { 
                    if ($task['status'] !== 'completed' && !empty($task['due_date'])) {
                        if ($task['due_date'] < $todayStr) {
                            $task['is_overdue_display'] = true;
                        }
                    }
                }
                unset($task); 
            }
            $response = ['success' => true, 'tasks' => $tasks];
            break;

        case 'get_task_details':
            $taskId = $requestData['id'] ?? null;

            if (empty($taskId)) {
                http_response_code(400);
                throw new Exception('Task ID is required to fetch details.');
            }

            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $taskId, ':user_id' => $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$task) {
                http_response_code(404);
                throw new Exception('Task not found or you do not have permission to view it.');
            }

            $activityStmt = $pdo->prepare(
                "SELECT activities.user_id, type, notes, timestamp, crop_id, inventory_id, livestock_id, quantity_change, profiles.full_name as user_full_name 
                 FROM activities 
                 LEFT JOIN profiles ON activities.user_id = profiles.user_id
                 WHERE activities.task_id = :task_id 
                 ORDER BY timestamp DESC"
            );
            $activityStmt->execute([':task_id' => $taskId]);
            $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
            $task['activities'] = $activities;
            
            $attachmentStmt = $pdo->prepare("SELECT id, file_name, file_path, file_type, uploaded_at FROM task_attachments WHERE task_id = :task_id AND user_id = :user_id");
            $attachmentStmt->execute([':task_id' => $taskId, ':user_id' => $userId]);
            $task['attachments'] = $attachmentStmt->fetchAll(PDO::FETCH_ASSOC);

            $relatedItemsStmt = $pdo->prepare("SELECT id, related_item_id, related_item_type FROM task_related_items WHERE task_id = :task_id AND user_id = :user_id");
            $relatedItemsStmt->execute([':task_id' => $taskId, ':user_id' => $userId]);
            $task['related_items'] = $relatedItemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['success' => true, 'task' => $task];
            break;

        case 'create_task':
            $title = trim($requestData['title'] ?? '');
            $description = trim($requestData['description'] ?? '');
            $assigned_to = trim($requestData['assigned_to'] ?? '');
            $due_date = !empty($requestData['due_date']) ? trim($requestData['due_date']) : null;
            $status = trim($requestData['status'] ?? 'pending');
            $priority = trim($requestData['priority'] ?? 'medium');
            $related_items = $requestData['related_items'] ?? [];


            if (empty($title)) {
                http_response_code(400);
                throw new Exception('Task title is required.');
            }
            if ($due_date !== null) {
                $d = DateTime::createFromFormat('Y-m-d', $due_date);
                if (!$d || $d->format('Y-m-d') !== $due_date) {
                    http_response_code(400);
                    throw new Exception('Invalid due date format. Please use YYYY-MM-DD.');
                }
            }
            if (empty($status)) $status = 'pending';
            if (empty($priority)) $priority = 'medium';

            $sql = "INSERT INTO tasks (user_id, title, description, assigned_to, due_date, status, priority, created_at, updated_at) 
                    VALUES (:user_id, :title, :description, :assigned_to, :due_date, :status, :priority, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId, ':title' => $title, ':description' => $description, ':assigned_to' => $assigned_to,
                ':due_date' => $due_date, ':status' => $status, ':priority' => $priority
            ]);
            $newTaskId = $pdo->lastInsertId();

            if ($newTaskId) {
                try {
                    $activitySql = "INSERT INTO activities (user_id, task_id, type, notes, timestamp, crop_id, inventory_id, livestock_id, quantity_change) 
                                    VALUES (:user_id, :task_id, :type, :notes, NOW(), NULL, NULL, NULL, NULL)";
                    $activityStmt = $pdo->prepare($activitySql);
                    $activityStmt->execute([
                        ':user_id' => $userId,
                        ':task_id' => $newTaskId,
                        ':type' => 'task_created',
                        ':notes' => "Task '" . htmlspecialchars($title) . "' created."
                    ]);
                } catch (PDOException $e) { error_log("Failed to log task creation activity for task ID {$newTaskId}: " . $e->getMessage()); }

                if (!empty($related_items) && is_array($related_items)) {
                    $relatedItemSql = "INSERT INTO task_related_items (task_id, user_id, related_item_id, related_item_type, created_at)
                                       VALUES (:task_id, :user_id, :related_item_id, :related_item_type, NOW())";
                    $relatedItemStmt = $pdo->prepare($relatedItemSql);
                    foreach ($related_items as $item) {
                        if (isset($item['related_item_id'], $item['related_item_type']) && 
                            !empty(trim($item['related_item_id'])) && 
                            !empty(trim($item['related_item_type'])) &&
                            in_array(trim($item['related_item_type']), $ALLOWED_RELATED_ITEM_TYPES)) {
                            try {
                                $relatedItemStmt->execute([
                                    ':task_id' => $newTaskId,
                                    ':user_id' => $userId,
                                    ':related_item_id' => trim($item['related_item_id']),
                                    ':related_item_type' => trim($item['related_item_type'])
                                ]);
                                $activityNotes = "Added related " . htmlspecialchars(trim($item['related_item_type'])) . ": ID " . htmlspecialchars(trim($item['related_item_id']));
                                $activityStmt->execute([ // Re-use $activityStmt for related item activity
                                    ':user_id' => $userId, ':task_id' => $newTaskId, ':type' => 'related_item_added',
                                    ':notes' => $activityNotes
                                ]);
                            } catch (PDOException $e) {
                                error_log("Failed to add related item or log activity ({$item['related_item_type']}:{$item['related_item_id']}) for task ID {$newTaskId}: " . $e->getMessage());
                            }
                        } else {
                             error_log("Invalid related item data for task ID {$newTaskId}: " . json_encode($item));
                        }
                    }
                }
                
                 if (isset($_FILES['attachments'])) {
                    $uploadDir = __DIR__ . '/../uploads/task_attachments/'; 
                    $filesToProcess = [];
                    if (is_array($_FILES['attachments']['name'])) { 
                        for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                            $filesToProcess[] = [
                                'name' => $_FILES['attachments']['name'][$i], 'type' => $_FILES['attachments']['type'][$i],
                                'tmp_name' => $_FILES['attachments']['tmp_name'][$i], 'error' => $_FILES['attachments']['error'][$i],
                                'size' => $_FILES['attachments']['size'][$i],
                            ];
                        }
                    } elseif ($_FILES['attachments']['error'] !== UPLOAD_ERR_NO_FILE) { 
                        $filesToProcess[] = $_FILES['attachments'];
                    }
                    foreach ($filesToProcess as $file) {
                        $attachmentData = saveTaskAttachment($file, $uploadDir);
                        if ($attachmentData) {
                            try {
                                $attachSql = "INSERT INTO task_attachments (task_id, user_id, file_name, file_path, file_type, uploaded_at)
                                              VALUES (:task_id, :user_id, :file_name, :file_path, :file_type, NOW())";
                                $attachStmt = $pdo->prepare($attachSql);
                                $attachStmt->execute([
                                    ':task_id' => $newTaskId, ':user_id' => $userId, ':file_name' => $attachmentData['file_name'],
                                    ':file_path' => $attachmentData['file_path'], ':file_type' => $attachmentData['file_type']
                                ]);
                            } catch (PDOException $e) {
                                error_log("Failed to insert attachment record for task ID {$newTaskId}, file {$attachmentData['file_name']}: " . $e->getMessage());
                            }
                        }
                    }
                }
                
                $response = ['success' => true, 'message' => 'Task created successfully!', 'taskId' => $newTaskId];
            } else {
                http_response_code(500);
                throw new Exception('Failed to create task. Could not get new task ID.');
            }
            break;

        case 'update_task':
            $taskId = $requestData['id'] ?? null;
            $related_items_data = $requestData['related_items'] ?? null; 

            if (empty($taskId)) { throw new Exception('Task ID is required for update.');}
            
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $taskId, ':user_id' => $userId]);
            $existingTask = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$existingTask) { http_response_code(404); throw new Exception('Task not found or you do not have permission to update it.');}
            $setClauses = []; $updateParams = [':id' => $taskId, ':user_id_where' => $userId]; $activityLogs = [];
            
             if (array_key_exists('title', $requestData)) {
                $newTitle = trim($requestData['title']);
                if (empty($newTitle)) {  throw new Exception('Task title cannot be empty if provided for update.'); }
                if ($newTitle !== $existingTask['title']) {
                    $setClauses[] = "title = :title"; $updateParams[':title'] = $newTitle;
                    $activityLogs[] = "Title changed from '" . htmlspecialchars($existingTask['title']) . "' to '" . htmlspecialchars($newTitle) . "'.";
                }
            }
            if (array_key_exists('description', $requestData)) {
                $newDescription = trim($requestData['description']);
                if ($newDescription !== $existingTask['description']) {
                    $setClauses[] = "description = :description"; $updateParams[':description'] = $newDescription;
                    $activityLogs[] = "Description updated.";
                }
            }
             if (array_key_exists('assigned_to', $requestData)) {
                $newAssignedTo = trim($requestData['assigned_to']);
                if ($newAssignedTo !== $existingTask['assigned_to']) {
                    $setClauses[] = "assigned_to = :assigned_to"; $updateParams[':assigned_to'] = $newAssignedTo;
                    $activityLogs[] = "Assigned to changed from '" . htmlspecialchars($existingTask['assigned_to'] ?: 'None') . "' to '" . htmlspecialchars($newAssignedTo ?: 'None') . "'.";
                }
            }
            if (array_key_exists('due_date', $requestData)) {
                $newDueDate = !empty($requestData['due_date']) ? trim($requestData['due_date']) : null;
                if ($newDueDate !== null) {
                    $d = DateTime::createFromFormat('Y-m-d', $newDueDate);
                    if (!$d || $d->format('Y-m-d') !== $newDueDate) { throw new Exception('Invalid due date format. Please use YYYY-MM-DD.');}
                }
                if ($newDueDate !== $existingTask['due_date']) {
                    $setClauses[] = "due_date = :due_date"; $updateParams[':due_date'] = $newDueDate;
                    $activityLogs[] = "Due date changed from '" . ($existingTask['due_date'] ?: 'None') . "' to '" . ($newDueDate ?: 'None') . "'.";
                }
            }
            if (array_key_exists('status', $requestData)) {
                $newStatus = trim($requestData['status']);
                if(empty($newStatus)) $newStatus = 'pending';
                if ($newStatus !== $existingTask['status']) {
                    $setClauses[] = "status = :status"; $updateParams[':status'] = $newStatus;
                    $activityLogs[] = "Status changed from '" . htmlspecialchars($existingTask['status']) . "' to '" . htmlspecialchars($newStatus) . "'.";
                }
            }
            if (array_key_exists('priority', $requestData)) {
                $newPriority = trim($requestData['priority']);
                if(empty($newPriority)) $newPriority = 'medium';
                if ($newPriority !== $existingTask['priority']) {
                    $setClauses[] = "priority = :priority"; $updateParams[':priority'] = $newPriority;
                    $activityLogs[] = "Priority changed from '" . htmlspecialchars($existingTask['priority']) . "' to '" . htmlspecialchars($newPriority) . "'.";
                }
            }

            if (!empty($setClauses)) {
                $setClauses[] = "updated_at = NOW()"; 
                $sql = "UPDATE tasks SET " . implode(", ", $setClauses) . " WHERE id = :id AND user_id = :user_id_where";
                $updateStmt = $pdo->prepare($sql);
                $updateSuccess = $updateStmt->execute($updateParams);
                if (!$updateSuccess) {
                    http_response_code(500);
                    throw new Exception('Failed to update task details in the database.');
                }
            }
            
            if (array_key_exists('related_items', $requestData)) {
                $deleteRelatedSql = "DELETE FROM task_related_items WHERE task_id = :task_id AND user_id = :user_id";
                $deleteRelatedStmt = $pdo->prepare($deleteRelatedSql);
                $deleteRelatedStmt->execute([':task_id' => $taskId, ':user_id' => $userId]);
                
                $related_items = $requestData['related_items']; 
                if (!empty($related_items) && is_array($related_items)) {
                    $relatedItemSql = "INSERT INTO task_related_items (task_id, user_id, related_item_id, related_item_type, created_at)
                                       VALUES (:task_id, :user_id, :related_item_id, :related_item_type, NOW())";
                    $relatedItemStmt = $pdo->prepare($relatedItemSql);
                    $addedItemsLog = [];
                    foreach ($related_items as $item) {
                        if (isset($item['related_item_id'], $item['related_item_type']) &&
                            !empty(trim($item['related_item_id'])) && 
                            !empty(trim($item['related_item_type'])) &&
                            in_array(trim($item['related_item_type']), $ALLOWED_RELATED_ITEM_TYPES)) {
                            try {
                                $relatedItemStmt->execute([
                                    ':task_id' => $taskId, ':user_id' => $userId,
                                    ':related_item_id' => trim($item['related_item_id']), ':related_item_type' => trim($item['related_item_type'])
                                ]);
                                $addedItemsLog[] = htmlspecialchars(trim($item['related_item_type'])) . ": " . htmlspecialchars(trim($item['related_item_id']));
                            } catch (PDOException $e) {
                                 error_log("Failed to add/update related item ({$item['related_item_type']}:{$item['related_item_id']}) for task ID {$taskId}: " . $e->getMessage());
                            }
                        } else {
                             error_log("Invalid related item data during update for task ID {$taskId}: " . json_encode($item));
                        }
                    }
                    if(!empty($addedItemsLog)){
                        $activityLogs[] = "Related items updated: " . implode(", ", $addedItemsLog) . ".";
                    } else {
                        $activityLogs[] = "All related items removed.";
                    }
                } else {
                     $activityLogs[] = "All related items removed.";
                }
            }

            if (isset($requestData['deleted_attachment_ids']) && is_array($requestData['deleted_attachment_ids'])) {
                $uploadDir = __DIR__ . '/../uploads/task_attachments/'; 
                foreach ($requestData['deleted_attachment_ids'] as $attachmentIdToDelete) {
                    $attachmentIdToDelete = (int)$attachmentIdToDelete;
                    $stmt = $pdo->prepare("SELECT file_name, file_path FROM task_attachments WHERE id = :id AND user_id = :user_id AND task_id = :task_id");
                    $stmt->execute([':id' => $attachmentIdToDelete, ':user_id' => $userId, ':task_id' => $taskId]);
                    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($attachment) {
                        $serverFilePath = $uploadDir . basename($attachment['file_path']); 
                        if (file_exists($serverFilePath)) { unlink($serverFilePath); }
                        $deleteAttachStmt = $pdo->prepare("DELETE FROM task_attachments WHERE id = :id");
                        $deleteAttachStmt->execute([':id' => $attachmentIdToDelete]);
                        $activityLogs[] = "Attachment '" . htmlspecialchars($attachment['file_name']) . "' deleted.";
                    } else { error_log("Attempt to delete non-existent or unauthorized attachment ID: {$attachmentIdToDelete}");}
                }
            }
            if (isset($_FILES['attachments'])) {
                $uploadDir = __DIR__ . '/../uploads/task_attachments/';
                $filesToProcess = [];
                 if (is_array($_FILES['attachments']['name'])) {
                    for ($i = 0; $i < count($_FILES['attachments']['name']); $i++) {
                         if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                        $filesToProcess[] = [
                            'name' => $_FILES['attachments']['name'][$i], 'type' => $_FILES['attachments']['type'][$i],
                            'tmp_name' => $_FILES['attachments']['tmp_name'][$i], 'error' => $_FILES['attachments']['error'][$i],
                            'size' => $_FILES['attachments']['size'][$i],
                        ];
                    }
                } elseif ($_FILES['attachments']['error'] !== UPLOAD_ERR_NO_FILE) { $filesToProcess[] = $_FILES['attachments']; }
                foreach ($filesToProcess as $file) {
                    $attachmentData = saveTaskAttachment($file, $uploadDir);
                    if ($attachmentData) {
                        try {
                            $attachSql = "INSERT INTO task_attachments (task_id, user_id, file_name, file_path, file_type, uploaded_at)
                                          VALUES (:task_id, :user_id, :file_name, :file_path, :file_type, NOW())";
                            $attachStmt = $pdo->prepare($attachSql);
                            $attachStmt->execute([
                                ':task_id' => $taskId, ':user_id' => $userId, ':file_name' => $attachmentData['file_name'],
                                ':file_path' => $attachmentData['file_path'], ':file_type' => $attachmentData['file_type']
                            ]);
                             $activityLogs[] = "Attachment '" . htmlspecialchars($attachmentData['file_name']) . "' added.";
                        } catch (PDOException $e) { error_log("Failed to insert attachment record for task ID {$taskId}: " . $e->getMessage()); }
                    }
                }
            }

            if (!empty($activityLogs)) {
                $activitySql = "INSERT INTO activities (user_id, task_id, type, notes, timestamp, crop_id, inventory_id, livestock_id, quantity_change) 
                                VALUES (:user_id, :task_id, :type, :notes, NOW(), NULL, NULL, NULL, NULL)";
                $activityStmt = $pdo->prepare($activitySql);
                $consolidatedNote = implode(" ", $activityLogs); 
                if (strlen($consolidatedNote) > 1000) { $consolidatedNote = substr($consolidatedNote, 0, 997) . "..."; }
                try {
                    $activityStmt->execute([
                        ':user_id' => $userId, ':task_id' => $taskId, ':type' => 'task_updated',
                        ':notes' => $consolidatedNote
                    ]);
                } catch (PDOException $e) { error_log("Failed to log consolidated task update activity for task ID {$taskId}: " . $e->getMessage());}
            }
            
            if (empty($setClauses) && empty($activityLogs) && !isset($_FILES['attachments']) && !isset($requestData['deleted_attachment_ids']) && !array_key_exists('related_items', $requestData)) {
                 $response = ['success' => true, 'message' => 'No changes detected for the task.'];
            } else {
                $response = ['success' => true, 'message' => 'Task updated successfully!'];
            }
            break;

        case 'delete_task':
            $taskId = $requestData['id'] ?? null;

            if (empty($taskId)) { throw new Exception('Task ID is required for deletion.');}
            
            $stmt = $pdo->prepare("SELECT title FROM tasks WHERE id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $taskId, ':user_id' => $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$task) { throw new Exception('Task not found or not permitted.');}
            $taskTitle = $task['title'];
            
            // Log deletion BEFORE actual deletion of task or its activities
            $deletionLogged = false;
            try {
                $logActivitySql = "INSERT INTO activities (user_id, task_id, type, notes, timestamp, crop_id, inventory_id, livestock_id, quantity_change) 
                                   VALUES (:user_id, :task_id, :type, :notes, NOW(), NULL, NULL, NULL, NULL)";
                $logNote = "Task '" . htmlspecialchars($taskTitle) . "' (ID: {$taskId}) deleted.";
                $logActivityStmt = $pdo->prepare($logActivitySql);
                $logActivityStmt->execute([
                    ':user_id' => $userId, ':task_id' => $taskId, ':type' => 'task_deleted',
                    ':notes' => $logNote
                ]);
                $deletionLogged = true;
            } catch (PDOException $e) { error_log("Critical: Failed to log task deletion activity for task ID {$taskId}: " . $e->getMessage()); }
            
            // Delete activities associated with this task_id.
            // This is good practice if ON DELETE CASCADE is not set or to be absolutely sure.
            try {
                $activitySql = "DELETE FROM activities WHERE task_id = :task_id";
                $activityStmt = $pdo->prepare($activitySql);
                $activityStmt->execute([':task_id' => $taskId]);
                error_log("Deleted activities for task_id {$taskId}. Rows affected: " . $activityStmt->rowCount());
            } catch (PDOException $e) {
                error_log("Error deleting activities for task_id {$taskId}: " . $e->getMessage() . ". Proceeding with task deletion.");
            }

            // Delete task attachments from server and DB
            $uploadDir = __DIR__ . '/../uploads/task_attachments/';
            $attStmt = $pdo->prepare("SELECT file_path, file_name FROM task_attachments WHERE task_id = :task_id AND user_id = :user_id");
            $attStmt->execute([':task_id' => $taskId, ':user_id' => $userId]);
            $attachments = $attStmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($attachments as $attachment) {
                $serverFilePath = $uploadDir . basename($attachment['file_path']);
                if (file_exists($serverFilePath)) {
                    unlink($serverFilePath);
                }
            }
            $deleteAttachStmt = $pdo->prepare("DELETE FROM task_attachments WHERE task_id = :task_id AND user_id = :user_id");
            $deleteAttachStmt->execute([':task_id' => $taskId, ':user_id' => $userId]);

            // Delete related items (if not handled by ON DELETE CASCADE, though DDL implies it is)
            $deleteRelatedStmt = $pdo->prepare("DELETE FROM task_related_items WHERE task_id = :task_id AND user_id = :user_id");
            $deleteRelatedStmt->execute([':task_id' => $taskId, ':user_id' => $userId]);


            // Delete the task itself
            $deleteSql = "DELETE FROM tasks WHERE id = :id AND user_id = :user_id";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteSuccess = $deleteStmt->execute([':id' => $taskId, ':user_id' => $userId]);

            if ($deleteSuccess && $deleteStmt->rowCount() > 0) {
                $response = ['success' => true, 'message' => "Task '" . htmlspecialchars($taskTitle) . "' deleted successfully!"];
            } else {
                http_response_code(404); 
                throw new Exception('Failed to delete task. It might have been already deleted or an issue occurred.');
            }
            break;
            
        case 'task_stats':
            $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = :user_id");
            $totalStmt->execute([':user_id' => $userId]);
            $total_tasks = (int) $totalStmt->fetchColumn();

            $completedStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = :user_id AND status = 'completed'");
            $completedStmt->execute([':user_id' => $userId]);
            $completed_tasks = (int) $completedStmt->fetchColumn();

            $overdueStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = :user_id AND due_date < CURDATE() AND status != 'completed'");
            $overdueStmt->execute([':user_id' => $userId]);
            $overdue_tasks = (int) $overdueStmt->fetchColumn();

            $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = :user_id AND status = 'pending' AND (due_date >= CURDATE() OR due_date IS NULL)");
            $pendingStmt->execute([':user_id' => $userId]);
            $pending_tasks = (int) $pendingStmt->fetchColumn();

            $inProgressStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = :user_id AND status = 'in-progress' AND (due_date >= CURDATE() OR due_date IS NULL)");
            $inProgressStmt->execute([':user_id' => $userId]);
            $in_progress_tasks = (int) $inProgressStmt->fetchColumn();
            
            $stats = [
                'total_tasks' => $total_tasks,
                'pending_tasks' => $pending_tasks,
                'in_progress_tasks' => $in_progress_tasks,
                'completed_tasks' => $completed_tasks,
                'overdue_tasks' => $overdue_tasks,
            ];

            $response = ['success' => true, 'stats' => $stats];
            break;

        case 'search_tasks':
            $query = trim($requestData['query'] ?? '');

            if (empty($query)) {
                $response = ['success' => true, 'tasks' => []];
                break;
            }

            $limit = 10;

            $sql = "SELECT id, title, description, status, due_date, priority 
                    FROM tasks 
                    WHERE user_id = :user_id 
                    AND (title LIKE :query OR description LIKE :query)
                    ORDER BY CASE
                        WHEN title LIKE :exact_query THEN 1
                        WHEN title LIKE :start_query THEN 2
                        ELSE 3
                    END, due_date ASC
                    LIMIT :limit";
            
            $searchParam = '%' . $query . '%';
            $exactQueryParam = $query;
            $startQueryParam = $query . '%';

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':query', $searchParam, PDO::PARAM_STR);
            $stmt->bindParam(':exact_query', $exactQueryParam, PDO::PARAM_STR);
            $stmt->bindParam(':start_query', $startQueryParam, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            
            $stmt->execute();
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ['success' => true, 'tasks' => $tasks];
            break;


        default:
            http_response_code(400); 
            throw new Exception('Unknown action: ' . htmlspecialchars($action));
    }

    if ($pdo->inTransaction()) {
        $pdo->commit();
    }
    echo json_encode($response);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Tasks API Error ({$action}): " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    $responseCode = 500; 
    if ($e->getCode() >= 400 && $e->getCode() < 600) { 
        $responseCode = $e->getCode();
    } else if (isset($e->code) && is_int($e->code) && $e->code >= 400 && $e->code < 600) { 
        $responseCode = $e->code;
    }

    if (!headers_sent()) { 
        http_response_code($responseCode);
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
