<?php
// Enable detailed error logging
ini_set('log_errors', 1);
// Ensure this log path is writable by the web server process
ini_set('error_log', __DIR__.'/../logs/php_errors.log');
ini_set('display_errors', 0); // NEVER display errors in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();

// Database connection
$pdo = require_once 'includes/db_connect.php'; // Assuming db_connect.php establishes $pdo

// Check for authenticated user
require_once 'includes/auth_functions.php'; // Assuming auth_functions.php has checkAuthentication()

// Set response header to JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];
$requestData = [];
$action = null;
$userId = $_SESSION['user_id'] ?? null; // Get user ID from session

// Ensure user is authenticated before processing any actions
if ($userId === null) {
    http_response_code(401); // Unauthorized
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

// Read input data based on Content-Type
$contentType = trim(explode(';', $_SERVER['CONTENT_TYPE'])[0] ?? '');

if ($contentType === 'application/json') {
    // If JSON, read from php://input
    $jsonInput = file_get_contents('php://input');
    $requestData = json_decode($jsonInput, true);
} else {
    // For multipart/form-data and application/x-www-form-urlencoded
    $requestData = $_POST;
    // Files are in $_FILES and will be accessed within the action cases
}

// Get the action from request data
$action = $requestData['action'] ?? null;

// Basic check if action is missing
if (empty($action)) {
    http_response_code(400); // Bad Request
    $response['message'] = 'Action not specified.';
    echo json_encode($response);
    exit;
}

// Helper function to log activity
function logActivity($userId, $activityType, $entityType, $entityId, $title, $description = null, $status = null, $additionalData = null) {
    global $pdo;
    
    $sql = "INSERT INTO activities (user_id, activity_type, entity_type, entity_id, title, description, status, additional_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $userId,
        $activityType,
        $entityType,
        $entityId,
        $title,
        $description,
        $status,
        $additionalData ? json_encode($additionalData) : null
    ]);
}

// Start database transaction
$pdo->beginTransaction();

try {
    // Debug log before switch
    error_log("Task API - Action received: " . $action);

    // Ensure action is not empty after potential parsing issues
    if (empty($action)) {
        throw new Exception('Invalid or empty action received.');
    }

    switch ($action) {
        case 'list_tasks':
            // Get filters and sorting from request data
            $search = $requestData['search'] ?? '';
            $status = $requestData['status'] ?? 'all';
            $priority = $requestData['priority'] ?? 'all';
            $sortBy = $requestData['sortBy'] ?? 'due_date-asc';
            $page = intval($requestData['page'] ?? 1);
            $itemsPerPage = intval($requestData['itemsPerPage'] ?? 10);
            
            // Calculate offset for pagination
            $offset = ($page - 1) * $itemsPerPage;
            
            // Base SQL query
            $sql = "SELECT * FROM tasks WHERE user_id = ?";
            $countSql = "SELECT COUNT(*) FROM tasks WHERE user_id = ?";
            $params = [$userId];
            $whereClauses = []; // Array to build WHERE clauses

            // Add search condition
            if (!empty($search)) {
                $whereClauses[] = "(title LIKE ? OR description LIKE ? OR assigned_to LIKE ?)";
                $searchParam = '%' . $search . '%';
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
            }

            // Add status filter condition
            if ($status !== 'all') {
                $whereClauses[] = "status = ?";
                $params[] = $status;
            }

            // Add priority filter condition
            if ($priority !== 'all') {
                $whereClauses[] = "priority = ?";
                $params[] = $priority;
            }

            // Combine WHERE clauses
            if (!empty($whereClauses)) {
                $sql .= " AND " . implode(" AND ", $whereClauses);
                $countSql .= " AND " . implode(" AND ", $whereClauses);
            }

            // Add ORDER BY clause
            $orderByMap = [
                'id-asc' => 'id ASC',
                'id-desc' => 'id DESC',
                'title-asc' => 'title ASC',
                'title-desc' => 'title DESC',
                'due_date-asc' => 'due_date ASC',
                'due_date-desc' => 'due_date DESC',
                'priority-asc' => 'FIELD(priority, "low", "medium", "high")',
                'priority-desc' => 'FIELD(priority, "high", "medium", "low")',
                'status-asc' => 'FIELD(status, "pending", "in-progress", "completed", "overdue")',
                'status-desc' => 'FIELD(status, "overdue", "pending", "in-progress", "completed")',
                'created_at-asc' => 'created_at ASC',
                'created_at-desc' => 'created_at DESC',
                'updated_at-asc' => 'updated_at ASC',
                'updated_at-desc' => 'updated_at DESC'
            ];
            
            $orderBy = $orderByMap[$sortBy] ?? 'due_date ASC, priority DESC'; // Default sort
            $sql .= " ORDER BY " . $orderBy;

            // Add LIMIT clause for pagination
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $itemsPerPage;
            $params[] = $offset;

            // Get total count for pagination
            $countStmt = $pdo->prepare($countSql);
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            $countStmt->closeCursor();

            // Prepare and execute the statement for tasks
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            // Return the list of tasks with pagination info
            $response = [
                'success' => true,
                'tasks' => $tasks,
                'pagination' => [
                    'totalItems' => $totalItems,
                    'itemsPerPage' => $itemsPerPage,
                    'currentPage' => $page,
                    'totalPages' => ceil($totalItems / $itemsPerPage)
                ]
            ];
            break;

        case 'get_task_details':
            // Get task ID from request data
            $taskId = $requestData['taskId'] ?? null;

            if ($taskId === null) {
                http_response_code(400); // Bad Request
                throw new Exception('Task ID not specified for details.');
            }

            // Fetch task details
            $sql = "SELECT * FROM tasks WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$taskId, $userId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($task) {
                // Fetch activities related to this task
                $activitiesSql = "SELECT * FROM activities WHERE entity_type = 'task' AND entity_id = ? ORDER BY timestamp DESC";
                $activitiesStmt = $pdo->prepare($activitiesSql);
                $activitiesStmt->execute([$taskId]);
                $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);
                $activitiesStmt->closeCursor();

                // Fetch comments for this task
                $commentsSql = "SELECT tc.*, p.full_name, p.profile_picture 
                               FROM task_comments tc 
                               LEFT JOIN profiles p ON tc.user_id = p.user_id 
                               WHERE tc.task_id = ? 
                               ORDER BY tc.created_at ASC";
                $commentsStmt = $pdo->prepare($commentsSql);
                $commentsStmt->execute([$taskId]);
                $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);
                $commentsStmt->closeCursor();

                // Fetch attachments for this task
                $attachmentsSql = "SELECT * FROM task_attachments WHERE task_id = ? ORDER BY uploaded_at DESC";
                $attachmentsStmt = $pdo->prepare($attachmentsSql);
                $attachmentsStmt->execute([$taskId]);
                $attachments = $attachmentsStmt->fetchAll(PDO::FETCH_ASSOC);
                $attachmentsStmt->closeCursor();

                // Fetch tags for this task
                $tagsSql = "SELECT tt.* FROM task_tags tt
                           JOIN task_tag_relations ttr ON tt.id = ttr.tag_id
                           WHERE ttr.task_id = ?";
                $tagsStmt = $pdo->prepare($tagsSql);
                $tagsStmt->execute([$taskId]);
                $tags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);
                $tagsStmt->closeCursor();

                // Add all related data to the task object
                $task['activities'] = $activities;
                $task['comments'] = $comments;
                $task['attachments'] = $attachments;
                $task['tags'] = $tags;

                // Return the task details
                $response = [
                    'success' => true,
                    'task' => $task
                ];
            } else {
                // Task not found or does not belong to the user
                http_response_code(404); // Not Found
                $response['message'] = 'Task not found.';
            }
            break;

        case 'create_task':
            // Get task data from request
            $title = trim($requestData['title'] ?? '');
            $description = trim($requestData['description'] ?? '');
            $assignedTo = trim($requestData['assignedTo'] ?? '');
            $dueDate = $requestData['dueDate'] ?? null;
            $status = $requestData['status'] ?? 'pending';
            $priority = $requestData['priority'] ?? 'medium';
            $relatedToType = $requestData['relatedToType'] ?? 'general';
            $relatedToId = intval($requestData['relatedToId'] ?? 0);
            $tags = $requestData['tags'] ?? [];

            // Basic validation
            if (empty($title)) {
                http_response_code(400); // Bad Request
                throw new Exception('Task title is required.');
            }

            // Insert new task
            $sql = "INSERT INTO tasks (user_id, title, description, assigned_to, due_date, status, priority, related_to_type, related_to_id, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $userId,
                $title,
                $description,
                $assignedTo,
                $dueDate,
                $status,
                $priority,
                $relatedToType,
                $relatedToId > 0 ? $relatedToId : null
            ]);
            $stmt->closeCursor();

            if ($success) {
                $newTaskId = $pdo->lastInsertId();

                // Log activity
                logActivity(
                    $userId,
                    'task_created',
                    'task',
                    $newTaskId,
                    "Task created: $title",
                    $description,
                    $status,
                    [
                        'priority' => $priority,
                        'due_date' => $dueDate,
                        'assigned_to' => $assignedTo
                    ]
                );

                // Handle tags if provided
                if (!empty($tags)) {
                    foreach ($tags as $tagName) {
                        // Check if tag exists
                        $tagSql = "SELECT id FROM task_tags WHERE name = ? AND user_id = ?";
                        $tagStmt = $pdo->prepare($tagSql);
                        $tagStmt->execute([trim($tagName), $userId]);
                        $tagId = $tagStmt->fetchColumn();
                        $tagStmt->closeCursor();

                        // If tag doesn't exist, create it
                        if (!$tagId) {
                            $createTagSql = "INSERT INTO task_tags (name, user_id, created_at) VALUES (?, ?, NOW())";
                            $createTagStmt = $pdo->prepare($createTagSql);
                            $createTagStmt->execute([trim($tagName), $userId]);
                            $tagId = $pdo->lastInsertId();
                            $createTagStmt->closeCursor();
                        }

                        // Associate tag with task
                        $tagRelationSql = "INSERT INTO task_tag_relations (task_id, tag_id) VALUES (?, ?)";
                        $tagRelationStmt = $pdo->prepare($tagRelationSql);
                        $tagRelationStmt->execute([$newTaskId, $tagId]);
                        $tagRelationStmt->closeCursor();
                    }
                }

                $response = [
                    'success' => true,
                    'message' => 'Task created successfully!',
                    'taskId' => $newTaskId
                ];
            } else {
                throw new Exception('Failed to create task.');
            }
            break;

        case 'update_task':
            // Get task data from request
            $taskId = $requestData['id'] ?? null;
            $title = trim($requestData['title'] ?? '');
            $description = trim($requestData['description'] ?? '');
            $assignedTo = trim($requestData['assignedTo'] ?? '');
            $dueDate = $requestData['dueDate'] ?? null;
            $status = $requestData['status'] ?? 'pending';
            $priority = $requestData['priority'] ?? 'medium';
            $relatedToType = $requestData['relatedToType'] ?? 'general';
            $relatedToId = intval($requestData['relatedToId'] ?? 0);
            $tags = $requestData['tags'] ?? [];

            // Basic validation
            if ($taskId === null || empty($title)) {
                http_response_code(400); // Bad Request
                throw new Exception('Task ID and title are required.');
            }

            // Verify task exists and belongs to the user
            $checkSql = "SELECT * FROM tasks WHERE id = ? AND user_id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$taskId, $userId]);
            $existingTask = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $checkStmt->closeCursor();

            if (!$existingTask) {
                http_response_code(404); // Not Found
                throw new Exception('Task not found or you do not have permission to update it.');
            }

            // Update task
            $sql = "UPDATE tasks SET 
                    title = ?, 
                    description = ?, 
                    assigned_to = ?, 
                    due_date = ?, 
                    status = ?, 
                    priority = ?, 
                    related_to_type = ?, 
                    related_to_id = ?, 
                    updated_at = NOW()
                    WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $title,
                $description,
                $assignedTo,
                $dueDate,
                $status,
                $priority,
                $relatedToType,
                $relatedToId > 0 ? $relatedToId : null,
                $taskId,
                $userId
            ]);
            $stmt->closeCursor();

            if ($success) {
                // Log activity for the update
                $activityType = 'task_updated';
                if ($status === 'completed' && $existingTask['status'] !== 'completed') {
                    $activityType = 'task_completed';
                }

                logActivity(
                    $userId,
                    $activityType,
                    'task',
                    $taskId,
                    "Task updated: $title",
                    $description,
                    $status,
                    [
                        'priority' => $priority,
                        'due_date' => $dueDate,
                        'assigned_to' => $assignedTo,
                        'previous_status' => $existingTask['status']
                    ]
                );

                // Handle tags - first remove all existing tag relations
                $deleteTagRelationsSql = "DELETE FROM task_tag_relations WHERE task_id = ?";
                $deleteTagRelationsStmt = $pdo->prepare($deleteTagRelationsSql);
                $deleteTagRelationsStmt->execute([$taskId]);
                $deleteTagRelationsStmt->closeCursor();

                // Then add new tag relations
                if (!empty($tags)) {
                    foreach ($tags as $tagName) {
                        // Check if tag exists
                        $tagSql = "SELECT id FROM task_tags WHERE name = ? AND user_id = ?";
                        $tagStmt = $pdo->prepare($tagSql);
                        $tagStmt->execute([trim($tagName), $userId]);
                        $tagId = $tagStmt->fetchColumn();
                        $tagStmt->closeCursor();

                        // If tag doesn't exist, create it
                        if (!$tagId) {
                            $createTagSql = "INSERT INTO task_tags (name, user_id, created_at) VALUES (?, ?, NOW())";
                            $createTagStmt = $pdo->prepare($createTagSql);
                            $createTagStmt->execute([trim($tagName), $userId]);
                            $tagId = $pdo->lastInsertId();
                            $createTagStmt->closeCursor();
                        }

                        // Associate tag with task
                        $tagRelationSql = "INSERT INTO task_tag_relations (task_id, tag_id) VALUES (?, ?)";
                        $tagRelationStmt = $pdo->prepare($tagRelationSql);
                        $tagRelationStmt->execute([$taskId, $tagId]);
                        $tagRelationStmt->closeCursor();
                    }
                }

                $response = [
                    'success' => true,
                    'message' => 'Task updated successfully!'
                ];
            } else {
                throw new Exception('Failed to update task.');
            }
            break;

        case 'delete_task':
            // Get task ID from request data
            $taskId = $requestData['id'] ?? null;

            if ($taskId === null) {
                http_response_code(400); // Bad Request
                throw new Exception('Task ID not specified for deletion.');
            }

            // Verify task exists and belongs to the user
            $checkSql = "SELECT title FROM tasks WHERE id = ? AND user_id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$taskId, $userId]);
            $taskTitle = $checkStmt->fetchColumn();
            $checkStmt->closeCursor();

            if (!$taskTitle) {
                http_response_code(404); // Not Found
                throw new Exception('Task not found or you do not have permission to delete it.');
            }

            // Log activity before deletion
            logActivity(
                $userId,
                'task_deleted',
                'task',
                $taskId,
                "Task deleted: $taskTitle",
                null,
                null,
                null
            );

            // Delete task
            $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$taskId, $userId]);
            $stmt->closeCursor();

            if ($success) {
                $response = [
                    'success' => true,
                    'message' => 'Task deleted successfully!'
                ];
            } else {
                throw new Exception('Failed to delete task.');
            }
            break;

        case 'add_comment':
            // Get comment data from request
            $taskId = $requestData['taskId'] ?? null;
            $comment = trim($requestData['comment'] ?? '');

            if ($taskId === null || empty($comment)) {
                http_response_code(400); // Bad Request
                throw new Exception('Task ID and comment text are required.');
            }

            // Verify task exists and belongs to the user
            $checkSql = "SELECT id FROM tasks WHERE id = ? AND user_id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$taskId, $userId]);
            $taskExists = $checkStmt->fetchColumn();
            $checkStmt->closeCursor();

            if (!$taskExists) {
                http_response_code(404); // Not Found
                throw new Exception('Task not found or you do not have permission to add a comment.');
            }

            // Insert comment
            $sql = "INSERT INTO task_comments (task_id, user_id, comment, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$taskId, $userId, $comment]);
            $stmt->closeCursor();

            if ($success) {
                $commentId = $pdo->lastInsertId();

                // Log activity
                logActivity(
                    $userId,
                    'note_added',
                    'task',
                    $taskId,
                    "Comment added to task",
                    $comment,
                    null,
                    null
                );

                // Get user profile info for the response
                $profileSql = "SELECT full_name, profile_picture FROM profiles WHERE user_id = ?";
                $profileStmt = $pdo->prepare($profileSql);
                $profileStmt->execute([$userId]);
                $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
                $profileStmt->closeCursor();

                $response = [
                    'success' => true,
                    'message' => 'Comment added successfully!',
                    'comment' => [
                        'id' => $commentId,
                        'task_id' => $taskId,
                        'user_id' => $userId,
                        'comment' => $comment,
                        'created_at' => date('Y-m-d H:i:s'),
                        'full_name' => $profile['full_name'] ?? 'Unknown User',
                        'profile_picture' => $profile['profile_picture'] ?? ''
                    ]
                ];
            } else {
                throw new Exception('Failed to add comment.');
            }
            break;

        case 'get_task_stats':
            // Get task statistics for the current user
            
            // Count tasks by status
            $statusSql = "SELECT status, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY status";
            $statusStmt = $pdo->prepare($statusSql);
            $statusStmt->execute([$userId]);
            $statusCounts = $statusStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $statusStmt->closeCursor();
            
            // Ensure all statuses have a count
            $allStatuses = ['pending', 'in-progress', 'completed', 'overdue'];
            foreach ($allStatuses as $status) {
                if (!isset($statusCounts[$status])) {
                    $statusCounts[$status] = 0;
                }
            }
            
            // Count tasks by priority
            $prioritySql = "SELECT priority, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY priority";
            $priorityStmt = $pdo->prepare($prioritySql);
            $priorityStmt->execute([$userId]);
            $priorityCounts = $priorityStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $priorityStmt->closeCursor();
            
            // Ensure all priorities have a count
            $allPriorities = ['low', 'medium', 'high'];
            foreach ($allPriorities as $priority) {
                if (!isset($priorityCounts[$priority])) {
                    $priorityCounts[$priority] = 0;
                }
            }
            
            // Count tasks by related entity type
            $relatedSql = "SELECT related_to_type, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY related_to_type";
            $relatedStmt = $pdo->prepare($relatedSql);
            $relatedStmt->execute([$userId]);
            $relatedCounts = $relatedStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $relatedStmt->closeCursor();
            
            // Get upcoming tasks (due in the next 7 days)
            $upcomingSql = "SELECT * FROM tasks WHERE user_id = ? AND due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status != 'completed' ORDER BY due_date ASC LIMIT 5";
            $upcomingStmt = $pdo->prepare($upcomingSql);
            $upcomingStmt->execute([$userId]);
            $upcomingTasks = $upcomingStmt->fetchAll(PDO::FETCH_ASSOC);
            $upcomingStmt->closeCursor();
            
            // Get overdue tasks
            $overdueSql = "SELECT * FROM tasks WHERE user_id = ? AND due_date < CURDATE() AND status != 'completed' ORDER BY due_date ASC LIMIT 5";
            $overdueStmt = $pdo->prepare($overdueSql);
            $overdueStmt->execute([$userId]);
            $overdueTasks = $overdueStmt->fetchAll(PDO::FETCH_ASSOC);
            $overdueStmt->closeCursor();
            
            // Get recently completed tasks
            $completedSql = "SELECT * FROM tasks WHERE user_id = ? AND status = 'completed' ORDER BY updated_at DESC LIMIT 5";
            $completedStmt = $pdo->prepare($completedSql);
            $completedStmt->execute([$userId]);
            $completedTasks = $completedStmt->fetchAll(PDO::FETCH_ASSOC);
            $completedStmt->closeCursor();
            
            // Return all statistics
            $response = [
                'success' => true,
                'stats' => [
                    'status' => $statusCounts,
                    'priority' => $priorityCounts,
                    'related' => $relatedCounts,
                    'upcoming' => $upcomingTasks,
                    'overdue' => $overdueTasks,
                    'completed' => $completedTasks,
                    'total' => array_sum($statusCounts)
                ]
            ];
            break;

        default:
            http_response_code(400); // Bad Request
            throw new Exception('Unknown action: ' . $action);
    }

    // Commit transaction if we got this far
    $pdo->commit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Log the error
    error_log("Task API Error: " . $e->getMessage());
    
    // Set error response
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

// Send the JSON response
echo json_encode($response);
exit;
