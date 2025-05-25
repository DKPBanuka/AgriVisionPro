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
// Make sure these paths are correct relative to crops_api.php
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

// --- Helper Functions for Image Handling (Adapted for Crops) ---
function saveImage($file, $uploadDir = '../uploads/crop_images/') {
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
        // Return a web-accessible path assuming ../uploads is web-accessible
        $webAccessiblePath = str_replace('../', '/', $filePath); // Example: /uploads/crop_images/filename.ext
        return $webAccessiblePath;
    } else {
        error_log("Failed to move uploaded file.");
        return false; // File move failed
    }
}

function deleteImage($imagePath) {
    // Ensure the path is within the expected upload directory for safety
    $uploadDir = '../uploads/crop_images/';
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
    error_log("Crops API - Action received before switch: " . $action);

    // Ensure action is not empty after potential parsing issues
    if (empty($action)) {
         throw new Exception('Invalid or empty action received.');
    }

    switch ($action) {
        case 'list_crops':
            // --- Debug Log ---
            error_log("Executing list_crops case.");

            // Get filters and sorting from request data (ensure they are safe to use)
            $search = $requestData['search'] ?? '';
            $cropType = $requestData['cropType'] ?? 'all'; // 'all' or specific type
            $status = $requestData['status'] ?? 'all'; // 'all', 'growing', 'harvested', 'planned', 'problem'
            $sortBy = $requestData['sortBy'] ?? 'id-asc'; // e.g., 'name-asc', 'planted-desc', 'harvest-asc'

            // Base SQL query
            $sql = "SELECT * FROM crops WHERE user_id = ?";
            $params = [$userId];
            $whereClauses = []; // Array to build WHERE clauses

            // Add search condition
            if (!empty($search)) {
                $whereClauses[] = "(name LIKE ? OR variety LIKE ? OR field LIKE ? OR notes LIKE ?)";
                $searchParam = '%' . $search . '%';
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
            }

            // Add crop type filter condition
            if ($cropType !== 'all') {
                $whereClauses[] = "crop_type = ?";
                $params[] = $cropType;
            }

            // Add status filter condition
            if ($status !== 'all') {
                $whereClauses[] = "status = ?";
                $params[] = $status;
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
                'planted-asc' => 'planted_date ASC',
                'planted-desc' => 'planted_date DESC',
                'harvest-asc' => 'harvest_date ASC',
                'harvest-desc' => 'harvest_date DESC',
                'updatedAt-asc' => 'updatedAt ASC',
                'updatedAt-desc' => 'updatedAt DESC',
                // Add other sorting options
            ];
            $orderBy = $orderByMap[$sortBy] ?? 'id ASC'; // Default sort
            $sql .= " ORDER BY " . $orderBy;

            // Prepare and execute the statement
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $crops = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all matching items
            $stmt->closeCursor();

            // Return the list of crops
            $response = [
                'success' => true,
                'crops' => $crops // Return the fetched array under 'crops' key
            ];
            break;

        case 'get_crop_details':
            // --- Debug Log ---
            error_log("Executing get_crop_details case.");

            // Get crop ID from request data
            $cropId = $requestData['cropId'] ?? null;

            if ($cropId === null) {
                http_response_code(400); // Bad Request
                throw new Exception('Crop ID not specified for details.');
            }

            // Fetch crop details from the crops table
            $sql = "SELECT * FROM crops WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cropId, $userId]);
            $crop = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch a single crop
            $stmt->closeCursor();

            if ($crop) {
                // Fetch activities related to this crop
                // Assuming an 'activities' table with a 'crop_id' foreign key
                $activitiesSql = "SELECT * FROM activities WHERE crop_id = ? AND user_id = ? ORDER BY timestamp DESC";
                $activitiesStmt = $pdo->prepare($activitiesSql);
                $activitiesStmt->execute([$cropId, $userId]);
                $activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC); // Fetch all activities for this crop
                $activitiesStmt->closeCursor();

                // Add the activities array to the crop object
                $crop['activities'] = $activities;

                // Return the crop details including activities
                $response = [
                    'success' => true,
                    'crop' => $crop // Return the single crop object under 'crop' key
                ];
            } else {
                // Crop not found or does not belong to the user
                 http_response_code(404); // Not Found
                $response['message'] = 'Crop not found.';
            }
            break;

        case 'create_crop':
            // --- Debug Log ---
            error_log("Executing create_crop case.");

            // Get crop data from requestData (sent via FormData)
            $name = trim($requestData['name'] ?? '');
            $variety = trim($requestData['variety'] ?? '');
            $cropType = $requestData['cropType'] ?? null;
            $field = trim($requestData['field'] ?? '');
            $area = floatval($requestData['area'] ?? 0);
            $plantedDate = $requestData['plantedDate'] ?? null;
            $harvestDate = $requestData['harvestDate'] ?? null;
            $status = $requestData['status'] ?? 'planned';
            $expectedYield = floatval($requestData['expectedYield'] ?? 0);
            $notes = trim($requestData['notes'] ?? '');
            // userId is already available in $userId

            // Basic validation
            if (empty($name) || empty($field) || $area <= 0 || empty($plantedDate)) {
                http_response_code(400); // Bad Request
                 throw new Exception('Required fields (Name, Field, Area, Planted Date) are missing or invalid.');
            }

            // Handle image upload if a file is provided
            $imagePath = null;
            if (isset($_FILES['cropImage']) && $_FILES['cropImage']['error'] !== UPLOAD_ERR_NO_FILE) {
                 $uploadResult = saveImage($_FILES['cropImage']);
                 if ($uploadResult === false) {
                    // saveImage logs the specific upload error
                     http_response_code(400); // Bad Request
                     throw new Exception('Image upload failed.');
                 } elseif ($uploadResult !== null) {
                     $imagePath = $uploadResult; // Path to the saved image
                 }
                 // If $uploadResult is null, it means no file was uploaded (which is handled by the isset check)
            }

            // Insert new crop into the crops table
            $sql = "INSERT INTO crops (user_id, name, variety, crop_type, field, area, planted_date, harvest_date, status, expected_yield, notes, image_url, createdAt, updatedAt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $userId,
                $name,
                $variety,
                $cropType,
                $field,
                $area,
                $plantedDate,
                $harvestDate,
                $status,
                $expectedYield,
                $notes,
                $imagePath // Save the image path
            ]);

            if ($success) {
                $newCropId = $pdo->lastInsertId(); // Get the ID of the newly inserted crop

                // Insert activity log for crop creation
                $activityType = 'crop_added';
                $activityNotes = "Crop '$name' added.";
                 
                 // Insert into activities table
                 $activitySql = "INSERT INTO activities (user_id, crop_id, type, notes, timestamp) VALUES (?, ?, ?, ?, NOW())";
                 $activityStmt = $pdo->prepare($activitySql);
                 $activityStmt->execute([$userId, $newCropId, $activityType, $activityNotes]);

                $response = ['success' => true, 'message' => 'Crop created successfully!', 'cropId' => $newCropId];
            } else {
                // Database insertion failed
                 error_log("Database insert failed for create_crop: " . implode(" ", $stmt->errorInfo()));
                // If image was uploaded, try to delete it to clean up
                if ($imagePath && file_exists('../'.$imagePath)) { // Check file exists before attempting delete
                     if (!deleteImage('../'.$imagePath)) { // Provide server path to deleteImage
                        error_log("Failed to clean up uploaded image after DB insert failure: ".$imagePath);
                     }
                }
                 throw new Exception('Failed to create crop.');
            }
            $stmt->closeCursor();

            break;

        case 'update_crop':
            // --- Debug Log ---
            error_log("Executing update_crop case.");

            // Get crop ID and data from requestData
            $cropId = $requestData['id'] ?? null;
            $name = trim($requestData['name'] ?? '');
            $variety = trim($requestData['variety'] ?? '');
            $cropType = $requestData['cropType'] ?? null;
            $field = trim($requestData['field'] ?? '');
            $area = floatval($requestData['area'] ?? 0);
            $plantedDate = $requestData['plantedDate'] ?? null;
            $harvestDate = $requestData['harvestDate'] ?? null;
            $status = $requestData['status'] ?? 'planned';
            $expectedYield = floatval($requestData['expectedYield'] ?? 0);
            $notes = trim($requestData['notes'] ?? '');
            $existingImageUrl = $requestData['existingImageUrl'] ?? null;

            // Basic validation
            if ($cropId === null || empty($name) || empty($field) || $area <= 0 || empty($plantedDate)) {
                http_response_code(400); // Bad Request
                throw new Exception('Required fields (ID, Name, Field, Area, Planted Date) are missing or invalid.');
            }

            // Verify crop exists and belongs to the user
            $checkSql = "SELECT id, image_url FROM crops WHERE id = ? AND user_id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$cropId, $userId]);
            $existingCrop = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $checkStmt->closeCursor();

            if (!$existingCrop) {
                http_response_code(404); // Not Found
                throw new Exception('Crop not found or does not belong to you.');
            }

            // Handle image upload if a file is provided
            $imagePath = $existingImageUrl; // Default to keeping the existing image
            if (isset($_FILES['cropImage']) && $_FILES['cropImage']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = saveImage($_FILES['cropImage']);
                if ($uploadResult === false) {
                    // saveImage logs the specific upload error
                    http_response_code(400); // Bad Request
                    throw new Exception('Image upload failed.');
                } elseif ($uploadResult !== null) {
                    // If upload successful, delete the old image if it exists
                    if (!empty($existingCrop['image_url'])) {
                        deleteImage('../' . $existingCrop['image_url']); // Provide server path to deleteImage
                    }
                    $imagePath = $uploadResult; // Path to the new image
                }
            } elseif (isset($requestData['removeImage']) && $requestData['removeImage'] === 'true') {
                // If user wants to remove the image without uploading a new one
                if (!empty($existingCrop['image_url'])) {
                    deleteImage('../' . $existingCrop['image_url']); // Provide server path to deleteImage
                }
                $imagePath = null; // Clear the image path
            }

            // Update crop in the database
            $sql = "UPDATE crops SET 
                    name = ?, 
                    variety = ?, 
                    crop_type = ?, 
                    field = ?, 
                    area = ?, 
                    planted_date = ?, 
                    harvest_date = ?, 
                    status = ?, 
                    expected_yield = ?, 
                    notes = ?, 
                    image_url = ?, 
                    updatedAt = NOW() 
                    WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $name,
                $variety,
                $cropType,
                $field,
                $area,
                $plantedDate,
                $harvestDate,
                $status,
                $expectedYield,
                $notes,
                $imagePath,
                $cropId,
                $userId
            ]);
            $stmt->closeCursor();

            if ($success) {
                // Log the update activity
                $activityType = 'crop_updated';
                $activityNotes = "Crop '$name' updated.";
                $activitySql = "INSERT INTO activities (user_id, crop_id, type, notes, timestamp) VALUES (?, ?, ?, ?, NOW())";
                $activityStmt = $pdo->prepare($activitySql);
                $activityStmt->execute([$userId, $cropId, $activityType, $activityNotes]);
                $activityStmt->closeCursor();

                $response = ['success' => true, 'message' => 'Crop updated successfully!'];
            } else {
                error_log("Database update failed for update_crop: " . implode(" ", $stmt->errorInfo()));
                throw new Exception('Failed to update crop.');
            }
            break;

        case 'delete_crop':
            // --- Debug Log ---
            error_log("Executing delete_crop case.");

            // Get crop ID from request data
            $cropId = $requestData['cropId'] ?? null;

            if ($cropId === null) {
                http_response_code(400); // Bad Request
                throw new Exception('Crop ID not specified for deletion.');
            }

            // Verify crop exists and belongs to the user, and get image path if any
            $checkSql = "SELECT id, image_url FROM crops WHERE id = ? AND user_id = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$cropId, $userId]);
            $existingCrop = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $checkStmt->closeCursor();

            if (!$existingCrop) {
                http_response_code(404); // Not Found
                throw new Exception('Crop not found or does not belong to you.');
            }

            // Delete related activities first (foreign key constraint)
            $deleteActivitiesSql = "DELETE FROM activities WHERE crop_id = ? AND user_id = ?";
            $deleteActivitiesStmt = $pdo->prepare($deleteActivitiesSql);
            $deleteActivitiesStmt->execute([$cropId, $userId]);
            $deleteActivitiesStmt->closeCursor();

            // Delete the crop
            $deleteSql = "DELETE FROM crops WHERE id = ? AND user_id = ?";
            $deleteStmt = $pdo->prepare($deleteSql);
            $success = $deleteStmt->execute([$cropId, $userId]);
            $deleteStmt->closeCursor();

            if ($success) {
                // Delete the associated image if it exists
                if (!empty($existingCrop['image_url'])) {
                    deleteImage('../' . $existingCrop['image_url']); // Provide server path to deleteImage
                }

                $response = ['success' => true, 'message' => 'Crop deleted successfully!'];
            } else {
                error_log("Database delete failed for delete_crop: " . implode(" ", $deleteStmt->errorInfo()));
                throw new Exception('Failed to delete crop.');
            }
            break;

        case 'get_crop_stats':
            // --- Debug Log ---
            error_log("Executing get_crop_stats case.");

            // Get crop statistics for dashboard/analytics
            
            // 1. Count crops by status
            $statusSql = "SELECT status, COUNT(*) as count FROM crops WHERE user_id = ? GROUP BY status";
            $statusStmt = $pdo->prepare($statusSql);
            $statusStmt->execute([$userId]);
            $statusStats = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
            $statusStmt->closeCursor();
            
            // 2. Sum area by crop type
            $areaSql = "SELECT crop_type, SUM(area) as total_area FROM crops WHERE user_id = ? GROUP BY crop_type";
            $areaStmt = $pdo->prepare($areaSql);
            $areaStmt->execute([$userId]);
            $areaStats = $areaStmt->fetchAll(PDO::FETCH_ASSOC);
            $areaStmt->closeCursor();
            
            // 3. Calculate expected yield by crop type
            $yieldSql = "SELECT crop_type, SUM(expected_yield * area) as total_yield FROM crops WHERE user_id = ? GROUP BY crop_type";
            $yieldStmt = $pdo->prepare($yieldSql);
            $yieldStmt->execute([$userId]);
            $yieldStats = $yieldStmt->fetchAll(PDO::FETCH_ASSOC);
            $yieldStmt->closeCursor();
            
            // Return all statistics
            $response = [
                'success' => true,
                'stats' => [
                    'status' => $statusStats,
                    'area' => $areaStats,
                    'yield' => $yieldStats
                ]
            ];
            break;

        default:
            // --- Debug Log ---
            error_log("Unknown action requested: " . $action);
            http_response_code(400); // Bad Request
            throw new Exception('Unknown action: ' . $action);
    }

    // If we got here, commit the transaction
    $pdo->commit();

    // Return the response as JSON
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback the transaction on error
    $pdo->rollBack();

    // Log the error
    error_log("Error in crops_api.php: " . $e->getMessage());

    // Set response code and message
    if (!isset($response['message']) || $response['message'] === 'Invalid request') {
        $response['message'] = $e->getMessage();
    }

    // Return error response as JSON
    echo json_encode($response);
}
?>
