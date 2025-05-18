<?php
// Enable detailed error logging
ini_set('log_errors', 1);
// Ensure this log path is writable by the web server process
// Adjust the path as necessary for your project structure (e.g., ../logs/php_errors.log)
ini_set('error_log', __DIR__.'/../logs/php_errors.log');
ini_set('display_errors', 0); // NEVER display errors in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();

// Database connection
// Make sure these paths are correct relative to inventory_api.php
require_once 'includes/db_connect.php'; // Assuming db_connect.php establishes $pdo

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

// Read input data based on Content-Type (Handling FormData for file uploads)
// Use $_POST for standard form data and FormData fields
// Use $_FILES for file uploads
// If receiving JSON, use php://input
$contentType = trim(explode(';', $_SERVER['CONTENT_TYPE'])[0] ?? '');

if ($contentType === 'application/json') {
    // If JSON, read from php://input
    $jsonInput = file_get_contents('php://input');
    $requestData = json_decode($jsonInput, true);
} else {
     // For multipart/form-data (FormData with files) and application/x-www-form-urlencoded
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

// --- Helper Functions for Image Handling (Adapted for Inventory) ---
function saveImage($file, $uploadDir = '../uploads/inventory_images/') {
    // Ensure upload directory exists and is writable
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true); // Create directory recursively
    }
     if (!is_writable($uploadDir)) {
        error_log("Upload directory is not writable: " . $uploadDir);
        return false; // Indicate failure
     }

    // Validate the uploaded file
    if (!isset($file['error']) || is_array($file['error'])) {
        error_log("Invalid file upload parameters.");
        return false;
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK: break; // File uploaded successfully
        case UPLOAD_ERR_NO_FILE: return null; // No file was uploaded
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            error_log("Uploaded file exceeds filesize limit.");
            return false; // File too large
        default:
             error_log("Unknown file upload error: " . $file['error']);
             return false; // Other upload errors
    }

    // Further checks: file size, MIME type
    // Check file size (e.g., max 5MB) - Should also be configured in php.ini and form max_file_size
    $maxFileSize = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxFileSize) {
         error_log("Uploaded file exceeds server-side filesize limit.");
         return false; // File too large
    }

    // Check MIME type using fileinfo extension (more reliable than client-provided type)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif']; // Allowed types
    if (!in_array($mimeType, $allowedMimeTypes)) {
         error_log("Invalid file MIME type: " . $mimeType);
         return false; // Invalid file type
    }

    // Generate a unique filename
    $fileName = md5(uniqid(rand(), true)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
    $filePath = $uploadDir . $fileName;

    // Move the uploaded file to the target directory
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return $filePath; // Return the path relative to the script or web root as needed
        // For web access, you might need to return a path like /uploads/inventory_images/filename.ext
        // Returning $filePath will give '../uploads/inventory_images/filename.ext'
        // Adjust the return value based on how your web server serves files.
        // Let's return a web-accessible path assuming ../uploads is web-accessible
        $webAccessiblePath = str_replace('../', '/', $filePath); // Example: /uploads/inventory_images/filename.ext
         return $webAccessiblePath;

    } else {
        error_log("Failed to move uploaded file.");
        return false; // File move failed
    }
}

function deleteImage($imagePath) {
    // Ensure the path is within the expected upload directory for safety
    $uploadDir = '../uploads/inventory_images/';
    // Resolve the real path of the provided image path and the upload directory
    $realUploadDir = realpath($uploadDir);
    $realImagePath = realpath($imagePath);

    // Check if the image path is valid, exists, and is within the upload directory
    if ($realImagePath && str_starts_with($realImagePath, $realUploadDir)) {
        if (file_exists($realImagePath)) {
            return unlink($realImagePath); // Delete the file
        }
    }
     error_log("Attempted to delete a file outside upload directory or non-existent: " . $imagePath);
     return false; // File not found, not in upload dir, or deletion failed
}


// Start database transaction
$pdo->beginTransaction();

try {
    // --- Debug Log before switch ---
    error_log("Inventory API - Action received before switch: " . $action);

    // Ensure action is not empty after potential parsing issues
    if (empty($action)) {
         throw new Exception('Invalid or empty action received.');
    }

    switch ($action) {
        case 'list_items':
            // --- Debug Log ---
            error_log("Executing list_items case.");

            // Get filters and sorting from request data (ensure they are safe to use)
            $search = $requestData['search'] ?? '';
            $category = $requestData['category'] ?? 'all'; // 'all' or specific category
            $status = $requestData['status'] ?? 'all'; // 'all', 'in-stock', 'low-stock', 'out-of-stock'
            $sortBy = $requestData['sortBy'] ?? 'id-asc'; // e.g., 'name-asc', 'quantity-desc', 'updatedAt-desc'

            // Base SQL query
            $sql = "SELECT * FROM inventory WHERE user_id = ?";
            $params = [$userId];
            $whereClauses = []; // Array to build WHERE clauses

            // Add search condition
            if (!empty($search)) {
                $whereClauses[] = "(name LIKE ? OR category LIKE ? OR location LIKE ? OR notes LIKE ?)";
                $searchParam = '%' . $search . '%';
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            }

            // Add category filter condition
            if ($category !== 'all') {
                $whereClauses[] = "category = ?";
                $params[] = $category;
            }

            // Add status filter condition (based on quantity)
            // Note: Low stock threshold is hardcoded here (e.g., < 10).
            // Ideally, this should be user configurable or in settings.
            $lowStockThreshold = 10; // Define your low stock threshold

            if ($status === 'in-stock') {
                $whereClauses[] = "quantity >= ?";
                $params[] = $lowStockThreshold;
            } elseif ($status === 'low-stock') {
                $whereClauses[] = "quantity > ? AND quantity < ?"; // Greater than 0 and less than threshold
                 $params[] = 0; // Assuming 0 or less is out of stock
                 $params[] = $lowStockThreshold;
            } elseif ($status === 'out-of-stock') {
                $whereClauses[] = "quantity <= ?"; // 0 or less
                $params[] = 0;
            }

            // Combine WHERE clauses
            if (!empty($whereClauses)) {
                $sql .= " AND " . implode(" AND ", $whereClauses);
            }

            // Add ORDER BY clause
            $orderByMap = [
                'id-asc' => 'id ASC',
                'id-desc' => 'id DESC',
                'name-asc' => 'name ASC',
                'name-desc' => 'name DESC',
                'quantity-asc' => 'quantity ASC',
                'quantity-desc' => 'quantity DESC',
                'updatedAt-asc' => 'updatedAt ASC',
                'updatedAt-desc' => 'updatedAt DESC',
                // Add other sorting options
            ];
            $orderBy = $orderByMap[$sortBy] ?? 'id ASC'; // Default sort
            $sql .= " ORDER BY " . $orderBy;

            // Prepare and execute the statement
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all matching items
            $stmt->closeCursor();

            // Return the list of items
            $response = [
                'success' => true,
                'inventory' => $inventory // Return the fetched array under 'inventory' key
            ];
            break;

        case 'get_item_details':
            // --- Debug Log ---
            error_log("Executing get_item_details case.");

            // Get item ID from request data
            $itemId = $requestData['itemId'] ?? null;

            if ($itemId === null) {
                http_response_code(400); // Bad Request
                throw new Exception('Item ID not specified for details.');
            }

            // Fetch item details from the inventory table
            $sql = "SELECT * FROM inventory WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$itemId, $userId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch a single item
            $stmt->closeCursor();

            if ($item) {
                // Fetch activities related to this item
                // Assuming an 'activities' table with an 'item_id' foreign key
                $activitiesSql = "SELECT * FROM activities WHERE item_id = ? AND user_id = ? ORDER BY timestamp DESC";
                $activitiesStmt = $pdo->prepare($activitiesSql);
                $activitiesStmt->execute([$itemId, $userId]);
                $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all activities for this item
                $activitiesStmt->closeCursor();

                // Add the activities array to the item object
                $item['activities'] = $activities;

                // Return the item details including activities
                $response = [
                    'success' => true,
                    'item' => $item // Return the single item object under 'item' key
                ];
            } else {
                // Item not found or does not belong to the user
                 http_response_code(404); // Not Found
                $response['message'] = 'Item not found.';
            }
            break;

        case 'create_item':
            // --- Debug Log ---
            error_log("Executing create_item case.");

            // Get item data from requestData (sent via FormData)
            $name = trim($requestData['name'] ?? '');
            $category = $requestData['category'] ?? null;
            $quantity = $requestData['quantity'] ?? 0;
            $unit = $requestData['unit'] ?? null;
            $location = trim($requestData['location'] ?? '');
            $notes = trim($requestData['notes'] ?? '');
            // userId is already available in $userId

            // Basic validation
            if (empty($name) || $quantity === null || $quantity < 0 || empty($unit)) {
                http_response_code(400); // Bad Request
                 throw new Exception('Required fields (Name, Quantity, Unit) are missing or invalid.');
            }

            // Handle image upload if a file is provided
            $imagePath = null;
            if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] !== UPLOAD_ERR_NO_FILE) {
                 $uploadResult = saveImage($_FILES['itemImage']);
                 if ($uploadResult === false) {
                    // saveImage logs the specific upload error
                     http_response_code(400); // Bad Request
                     throw new Exception('Image upload failed.');
                 } elseif ($uploadResult !== null) {
                     $imagePath = $uploadResult; // Path to the saved image
                 }
                 // If $uploadResult is null, it means no file was uploaded (which is handled by the isset check)
            }

            // Insert new item into the inventory table
            $sql = "INSERT INTO inventory (user_id, name, category, quantity, unit, location, notes, image_url, createdAt, updatedAt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $userId,
                $name,
                $category,
                $quantity,
                $unit,
                $location,
                $notes,
                $imagePath // Save the image path
            ]);

            if ($success) {
                $newItemId = $pdo->lastInsertId(); // Get the ID of the newly inserted item

                // --- FIX: Log activity linked to the new item_id ---
                // Insert activity log for item creation
                $activityType = 'item_added';
                $activityNotes = "Item '$name' added.";
                 // Optional: Log initial quantity
                 $activityQuantityChange = $quantity; // Log the initial quantity as a change
                 // Insert into activities table
                 $activitySql = "INSERT INTO activities (user_id, item_id, type, notes, timestamp, quantity_change) VALUES (?, ?, ?, ?, NOW(), ?)";
                 $activityStmt = $pdo->prepare($activitySql);
                 $activityStmt->execute([$userId, $newItemId, $activityType, $activityNotes, $activityQuantityChange]);


                $response = ['success' => true, 'message' => 'Item created successfully!', 'itemId' => $newItemId];
            } else {
                // Database insertion failed
                 error_log("Database insert failed for create_item: " . implode(" ", $stmt->errorInfo()));
                // If image was uploaded, try to delete it to clean up
                if ($imagePath && file_exists('../'.$imagePath)) { // Check file exists before attempting delete
                     if (!deleteImage('../'.$imagePath)) { // Provide server path to deleteImage
                        error_log("Failed to clean up uploaded image after DB insert failure: ".$imagePath);
                     }
                }
                 throw new Exception('Failed to create item.');
            }
            $stmt->closeCursor();

            break;

        case 'update_item':
            // --- Debug Log ---
            error_log("Executing update_item case.");

            // Get item ID and data from requestData
            $itemId = $requestData['id'] ?? null;
            $name = trim($requestData['name'] ?? '');
            $category = $requestData['category'] ?? null;
            $quantity = $requestData['quantity'] ?? 0;
            $unit = $requestData['unit'] ?? null;
            $location = trim($requestData['location'] ?? '');
            $notes = trim($requestData['notes'] ?? '');
            $removeExistingImage = ($requestData['removeExistingImage'] ?? 'false') === 'true'; // Check flag
            $existingImagePath = $requestData['existingImage'] ?? null; // Existing path from hidden field

            if ($itemId === null) {
                http_response_code(400); // Bad Request
                throw new Exception('Item ID not specified for update.');
            }

            // Basic validation
             if (empty($name) || $quantity === null || $quantity < 0 || empty($unit)) {
                http_response_code(400); // Bad Request
                 throw new Exception('Required fields (Name, Quantity, Unit) are missing or invalid.');
            }


            // Fetch the current item data to check existing image and old quantity
            $fetchSql = "SELECT quantity, image_url FROM inventory WHERE id = ? AND user_id = ?";
            $fetchStmt = $pdo->prepare($fetchSql);
            $fetchStmt->execute([$itemId, $userId]);
            $currentItem = $fetchStmt->fetch(PDO::FETCH_ASSOC);
            $fetchStmt->closeCursor();

            if (!$currentItem) {
                 http_response_code(404); // Not Found
                 throw new Exception('Item not found for update.');
            }

            $oldQuantity = $currentItem['quantity'];
            $oldImagePath = $currentItem['image_url']; // Path stored in DB

            // Handle image update/deletion
            $newImagePath = $oldImagePath; // Start with the existing path

            if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] !== UPLOAD_ERR_NO_FILE) {
                 // A new file was uploaded
                 $uploadResult = saveImage($_FILES['itemImage']);
                 if ($uploadResult === false) {
                     // saveImage logs the specific upload error
                      http_response_code(400); // Bad Request
                      throw new Exception('New image upload failed.');
                 } elseif ($uploadResult !== null) {
                     $newImagePath = $uploadResult; // Use the path of the new image
                     // If there was an old image, delete it
                     if ($oldImagePath && deleteImage($oldImagePath)) {
                         error_log("Deleted old image: " . $oldImagePath);
                     }
                 }
                 // If $uploadResult is null, it means no file was actually selected/uploaded despite input change
                 // In this case, $newImagePath remains $oldImagePath

            } elseif ($removeExistingImage) {
                 // Signal was given to remove the existing image
                 if ($oldImagePath && deleteImage($oldImagePath)) {
                     error_log("Deleted existing image as requested: " . $oldImagePath);
                     $newImagePath = null; // Clear the image path in DB
                 } elseif ($oldImagePath) {
                     // Attempted to delete but failed (e.g., file not found or permissions)
                     error_log("Failed to delete existing image as requested: " . $oldImagePath);
                     // Decide whether to continue with update or throw error
                     // For now, log and continue update without changing image_url
                 } else {
                     // No old image existed anyway, nothing to delete
                      $newImagePath = null; // Ensure path is null if no old image existed
                 }
            } else {
                // No new file uploaded, and no signal to remove existing.
                // $newImagePath remains $oldImagePath (the path from the DB)
                // However, if the form didn't send back the existingImage path correctly,
                // $newImagePath might become null unintentionally.
                // Let's rely on the $existingImagePath sent from the hidden field
                 if ($existingImagePath && !$removeExistingImage) {
                     $newImagePath = $existingImagePath;
                 } else {
                     // If $existingImagePath was empty or remove was true, ensure $newImagePath is null
                     $newImagePath = null;
                 }
            }


            // Update item in the inventory table
            $sql = "UPDATE inventory SET name = ?, category = ?, quantity = ?, unit = ?, location = ?, notes = ?, image_url = ?, updatedAt = NOW()
                    WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $name,
                $category,
                $quantity,
                $unit,
                $location,
                $notes,
                $newImagePath, // Save the new/updated image path (can be null)
                $itemId,
                $userId
            ]);

            if ($success) {
                 // Check if any rows were actually updated (item exists and belongs to user)
                 if ($stmt->rowCount() > 0) {

                     // --- FIX: Log activity linked to item_id for update and quantity change ---
                     $activityType = 'item_updated';
                     $activityNotes = "Item '$name' updated.";
                     $activityQuantityChange = null; // Default: no quantity change logged by update action itself

                     // Check for quantity change and log a specific activity if it happened
                     if ((float)$quantity !== (float)$oldQuantity) { // Compare quantities as floats
                          $quantityDifference = (float)$quantity - (float)$oldQuantity;
                          $activityQuantityChange = $quantityDifference; // Log the difference
                          $activityNotes .= " Quantity changed from $oldQuantity to $quantity."; // Add note about change
                          $activityType = $quantityDifference > 0 ? 'stock_added' : 'stock_removed'; // Log specific stock change type

                          // Also check for low stock/out of stock status changes and log notifications
                          $oldStatus = calculateInventoryStatus((float)$oldQuantity, $lowStockThreshold); // Use helper to calculate status
                          $newStatus = calculateInventoryStatus((float)$quantity, $lowStockThreshold);

                          if ($oldStatus !== $newStatus) {
                              if ($newStatus === 'low-stock') {
                                   // Log notification for low stock
                                   $notificationType = 'item_low_stock';
                                   $notificationMessage = "Item '$name' is now low in stock (Qty: $quantity).";
                                    logNotification($userId, $notificationType, $notificationMessage, $itemId); // Log notification

                              } elseif ($newStatus === 'out-of-stock') {
                                   // Log notification for out of stock
                                   $notificationType = 'item_out_of_stock';
                                   $notificationMessage = "Item '$name' is now out of stock.";
                                    logNotification($userId, $notificationType, $notificationMessage, $itemId); // Log notification
                              }
                              // You might also log an activity for the status change itself if needed
                          }

                     } else {
                         // No quantity change, just log the generic update
                         $activityQuantityChange = null; // Ensure this is null if no quantity change detected here
                     }

                     // Insert the main update or stock change activity
                      $activitySql = "INSERT INTO activities (user_id, item_id, type, notes, timestamp, quantity_change) VALUES (?, ?, ?, ?, NOW(), ?)";
                      $activityStmt = $pdo->prepare($activitySql);
                      $activityStmt->execute([$userId, $itemId, $activityType, $activityNotes, $activityQuantityChange]);


                     $response = ['success' => true, 'message' => 'Item updated successfully!'];
                 } else {
                     // Item ID exists but doesn't belong to the user or no changes were made
                     // Treat as not found for security/correctness
                     http_response_code(404); // Not Found
                     throw new Exception('Item not found or does not belong to the user.');
                 }
            } else {
                // Database update failed
                 error_log("Database update failed for update_item ID $itemId: " . implode(" ", $stmt->errorInfo()));
                 throw new Exception('Failed to update item.');
            }
            $stmt->closeCursor();

            break;

        case 'delete_item':
            // --- Debug Log ---
            error_log("Executing delete_item case.");

            // Get item ID from requestData
            $itemId = $requestData['itemId'] ?? null;

            if ($itemId === null) {
                http_response_code(400); // Bad Request
                throw new Exception('Item ID not specified for deletion.');
            }

            // First, fetch the item to get the image URL and name for activity log/deletion
            $fetchSql = "SELECT name, image_url FROM inventory WHERE id = ? AND user_id = ?";
            $fetchStmt = $pdo->prepare($fetchSql);
            $fetchStmt->execute([$itemId, $userId]);
            $itemToDelete = $fetchStmt->fetch(PDO::FETCH_ASSOC);
            $fetchStmt->closeCursor();

            if (!$itemToDelete) {
                 // Item not found or does not belong to the user
                 http_response_code(404); // Not Found
                 throw new Exception('Item not found for deletion.');
            }

            $itemName = $itemToDelete['name'] ?? 'Unnamed Item';
            $imagePathToDelete = $itemToDelete['image_url']; // Path stored in DB

            // Delete the item from the inventory table
            $deleteSql = "DELETE FROM inventory WHERE id = ? AND user_id = ?";
            $deleteStmt = $pdo->prepare($deleteSql);
            $success = $deleteStmt->execute([$itemId, $userId]);

            if ($success) {
                 // Check if any rows were actually deleted
                 if ($deleteStmt->rowCount() > 0) {

                     // --- FIX: Delete associated activities ---
                     // Delete all activities linked to this item
                     $deleteActivitiesSql = "DELETE FROM activities WHERE item_id = ? AND user_id = ?";
                     $deleteActivitiesStmt = $pdo->prepare($deleteActivitiesSql);
                     $deleteActivitiesStmt->execute([$itemId, $userId]);


                     // --- FIX: Delete associated image file ---
                     if ($imagePathToDelete) {
                         if (deleteImage($imagePathToDelete)) { // Provide path from DB to deleteImage
                            error_log("Deleted image after item deletion: " . $imagePathToDelete);
                         } else {
                            error_log("Failed to delete image after item deletion: " . $imagePathToDelete);
                         }
                     }

                    // --- FIX: Log activity for item deletion ---
                     $activityType = 'item_deleted';
                     $activityNotes = "Item '$itemName' deleted.";
                     // Insert into activities table (no item_id link as item is deleted, but log for user)
                     // Decide if you want to log deletion activity WITHOUT an item_id link, or if
                     // activities MUST link to an item. If activities MUST link, this log can't happen
                     // in the 'activities' table if item_id is a strict FK.
                     // Assuming activities can exist without an item_id (e.g., user deleted an item)
                      $activitySql = "INSERT INTO activities (user_id, type, notes, timestamp) VALUES (?, ?, ?, NOW())";
                      $activityStmt = $pdo->prepare($activitySql);
                      $activityStmt->execute([$userId, $activityType, $activityNotes]);


                    $response = ['success' => true, 'message' => 'Item deleted successfully!'];
                 } else {
                     // Item ID exists but doesn't belong to the user or was already deleted
                     http_response_code(404); // Not Found
                    $response['message'] = 'Item not found or does not belong to the user.';
                 }
            } else {
                // Database deletion failed
                 error_log("Database delete failed for delete_item ID $itemId: " . implode(" ", $deleteStmt->errorInfo()));
                 throw new Exception('Failed to delete item.');
            }
            $deleteStmt->closeCursor(); // This is not needed after DELETE

            break;

        case 'stats':
             // --- Debug Log ---
             error_log("Executing stats case.");

             // Low stock threshold (should match frontend and update logic)
             $lowStockThreshold = 10;

             // SQL to get total count
             $totalSql = "SELECT COUNT(*) FROM inventory WHERE user_id = ?";
             $totalStmt = $pdo->prepare($totalSql);
             $totalStmt->execute([$userId]);
             $totalCount = $totalStmt->fetchColumn();

             // SQL to get counts by status
             $statusSql = "SELECT
                            SUM(CASE WHEN quantity >= ? THEN 1 ELSE 0 END) AS in_stock_count,
                            SUM(CASE WHEN quantity > 0 AND quantity < ? THEN 1 ELSE 0 END) AS low_stock_count,
                            SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END) AS out_of_stock_count
                          FROM inventory WHERE user_id = ?";
             $statusStmt = $pdo->prepare($statusSql);
             $statusStmt->execute([$lowStockThreshold, $lowStockThreshold, $userId]);
             $statusCounts = $statusStmt->fetch(PDO::FETCH_ASSOC);

             // SQL to get counts by category
             $categorySql = "SELECT category, COUNT(*) AS count FROM inventory WHERE user_id = ? GROUP BY category";
             $categoryStmt = $pdo->prepare($categorySql);
             $categoryStmt->execute([$userId]);
             $categoryCounts = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

              // SQL to get count by location (optional)
             // $locationSql = "SELECT location, COUNT(*) AS count FROM inventory WHERE user_id = ? GROUP BY location";
             // $locationStmt = $pdo->prepare($locationSql);
             // $locationStmt->execute([$userId]);
             // $locationCounts = $locationStmt->fetchAll(PDO::FETCH_ASSOC);


             $response = [
                 'success' => true,
                 'stats' => [
                     'total' => (int) $totalCount,
                     'in_stock' => (int) $statusCounts['in_stock_count'],
                     'low_stock' => (int) $statusCounts['low_stock_count'],
                     'out_of_stock' => (int) $statusCounts['out_of_stock_count'],
                     'category_counts' => $categoryCounts,
                     // 'location_counts' => $locationCounts, // Include if fetched
                 ]
             ];
             break;

        case 'search':
            // --- Debug Log ---
             error_log("Executing search case.");

             $query = $requestData['query'] ?? '';
             $query = trim($query); // Trim whitespace

             if (empty($query)) {
                  // If search query is empty, maybe return a small number of recent items or an empty list
                  // Returning empty for now as per quick search UX
                  $response = ['success' => true, 'items' => []];
                  break;
             }

             // Limit search results for quick dropdown (e.g., 10 results)
             $limit = 10;

             // SQL to search inventory items by user and query in relevant fields
             $sql = "SELECT id, name, category, quantity, unit, image_url FROM inventory
                     WHERE user_id = ? AND
                     (name LIKE ? OR category LIKE ? OR location LIKE ? OR notes LIKE ?)
                     LIMIT ?"; // Limit the number of results

             $searchParam = '%' . $query . '%';
             $params = [$userId, $searchParam, $searchParam, $searchParam, $searchParam, $limit];

             $stmt = $pdo->prepare($sql);
             $stmt->execute($params);
             $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
             $stmt->closeCursor();

             $response = [
                 'success' => true,
                 'items' => $items // Return the matching items
             ];
             break;


         // Helper function to calculate inventory status (Replicated from PHP in inventory.php)
         // Define here if needed for server-side status calculation/logging
         function calculateInventoryStatus($quantity, $lowStockThreshold = 10) {
             if ($quantity <= 0) return 'out-of-stock';
             if ($quantity < $lowStockThreshold) return 'low-stock';
             return 'in-stock';
         }

         // Helper function to log notifications (Assuming a 'notifications' table)
         // Requires a 'notifications' table with columns like id, user_id, type, message, timestamp, read
         function logNotification($userId, $type, $message, $itemId = null) {
             global $pdo; // Use the global PDO connection

             try {
                  $sql = "INSERT INTO notifications (user_id, type, message, item_id, timestamp, read_status) VALUES (?, ?, ?, ?, NOW(), 'unread')";
                  $stmt = $pdo->prepare($sql);
                  $stmt->execute([$userId, $type, $message, $itemId]);
                  error_log("Logged notification for user $userId: $message (Item ID: $itemId)");
             } catch (PDOException $e) {
                  error_log("Failed to log notification for user $userId: " . $e->getMessage());
             }
         }


        default:
            // Unknown action requested
            http_response_code(400); // Bad Request
             throw new Exception('Unknown action: ' . htmlspecialchars($action));
            break;
    }

    // If execution reaches here, commit the transaction
    $pdo->commit();
    // Send the final JSON response
    echo json_encode($response);

} catch (Exception $e) {
    // Log the error on the server side
    error_log('Inventory API Error: ' . $e->getMessage());

    // Rollback the transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Set an appropriate HTTP status code if not already set by a specific case
    if (!http_response_code() || http_response_code() < 400) {
         http_response_code(500); // Internal Server Error by default for uncaught exceptions
    }

    // Send error response to the client
    $response = ['success' => false, 'message' => $e->getMessage()];
    echo json_encode($response);

}

// The exit() is not strictly needed after echo json_encode in an API context where no further processing is expected.
// exit();

?>