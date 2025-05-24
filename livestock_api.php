<?php
// Enable detailed error logging
ini_set('log_errors', 1);
// Ensure this log path is writable by the web server process
ini_set('error_log', __DIR__.'/../logs/php_errors.log'); // Path එක නිවැරදි දැයි නැවත පරීක්ෂා කරන්න
ini_set('display_errors', 0); // Production එකේදී දෝෂ සෘජුව පෙන්වීම disabled කරන්න
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

session_start();

// Database connection
// Make sure these paths are correct relative to livestock_api.php
require_once 'includes/db_connect.php'; // Assuming db_connect.php establishes $pdo

// Check for authenticated user
require_once 'includes/auth_functions.php'; // Assuming auth_functions.php has checkAuthentication()

// Set response header to JSON
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

// Read input data based on Content-Type or action
$requestData = [];
$action = null;

// Check Content-Type header
$contentType = trim(explode(';', $_SERVER['CONTENT_TYPE'])[0]);

if ($contentType === 'application/json') {
    // If JSON, read from php://input
    $jsonInput = file_get_contents('php://input');
    $requestData = json_decode($jsonInput, true);
    $action = $requestData['action'] ?? null;
} else if ($contentType === 'multipart/form-data' || $contentType === 'application/x-www-form-urlencoded') {
    // If FormData or URL-encoded form, data is in $_POST and files in $_FILES
    $requestData = $_POST;
    $action = $requestData['action'] ?? null;
    // Files are accessed via $_FILES directly within the action cases
} else {
    // Handle other content types or no content type
    // For simplicity, default to trying POST data if content type is not explicitly JSON
    if (!empty($_POST)) {
         $requestData = $_POST;
         $action = $requestData['action'] ?? null;
    } else {
        // No data received in expected format
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Unsupported Content-Type or no data received.']);
        exit; // Stop execution
    }
}


try {
    // Validate input data after reading
    if ($requestData === null && $contentType === 'application/json') {
        http_response_code(400); // Bad Request
        throw new Exception('Invalid JSON data received');
    }

    // $action and $requestData are now populated based on the request

    $userId = $_SESSION['user_id'] ?? null; // Get user ID from session

    // !!! IMPORTANT: Authenticate User !!!
    if (empty($userId)) {
        http_response_code(401); // Unauthorized
        throw new Exception('User not logged in');
    }

    // Check if action is provided
    if (empty($action)) {
         http_response_code(400); // Bad Request
         throw new Exception('Action not specified.');
    }


    // Use PDO connection provided by db_connect.php
    global $pdo;

    if (!$pdo) {
         throw new Exception('Database connection not established.');
    }

    // ... (Initial request handling code ending with assigning $action) ...

    // --- මෙම Line එක එකතු කරන්න ---
    error_log("Action received before switch: " . $action); // Check action value here

    // Basic check if action is empty (This part is usually after reading action)
    // if (empty($action)) { ... }

    // --- switch statement starts here ---
    switch ($action) {
        // ... case 'add_livestock': ... break;
        // ... case 'update_livestock': ... break;
        case 'get_livestock':
             error_log("Executing get_livestock case."); // Add log inside case
            // ... your get_livestock logic ...
            break; // MUST HAVE BREAK

        case 'get_animal_details':
             error_log("Executing get_animal_details case."); // Add log inside case
            // ... your get_animal_details logic ...
            break; // MUST HAVE BREAK

        // ... other cases ...

        default:
             error_log("Executing default case. Unknown action: " . $action); // Add log inside default
            break;
    }
    // ... (Rest of the script) ...


    switch ($action) {
        // livestock_api.php script තුළ
    // switch ($action) Block එක තුළ

    case 'add_animal':
        // --- Add a new animal ---
        // This action expects JSON data
         $contentType = trim(explode(';', $_SERVER['CONTENT_TYPE'])[0]);
         if ($contentType !== 'application/json') {
              http_response_code(400); // Bad Request
              throw new Exception('Invalid Content-Type for add_animal. Expected application/json.');
         }

        $jsonInput = file_get_contents('php://input');
        $requestData = json_decode($jsonInput, true);

        if ($requestData === null) {
            http_response_code(400); // Bad Request
            throw new Exception('Invalid JSON data received');
        }

        // Validate required fields (adjust as per your form)
        $requiredFields = ['idTag', 'type', 'birthDate', 'ageYears', 'ageMonths', 'weight', 'weightUnit', 'status', 'location', 'notes']; // Add other required fields

        foreach ($requiredFields as $field) {
            if (!isset($requestData[$field])) {
                http_response_code(400); // Bad Request
                throw new Exception("Missing required field: $field");
            }
        }

        // Get data from the request
        $idTag = $requestData['idTag'];
        $type = $requestData['type'];
        $birthDate = !empty($requestData['birthDate']) ? $requestData['birthDate'] : null; // Allow null for birthDate
        $ageYears = $requestData['ageYears'];
        $ageMonths = $requestData['ageMonths'];
        $weight = $requestData['weight'];
        $weightUnit = $requestData['weightUnit'];
        $status = $requestData['status'];
        $location = $requestData['location'];
        $notes = $requestData['notes'];
        $breed = $requestData['breed'] ?? null; // Breed might be optional
        $sex = $requestData['sex'] ?? null; // Assuming you might add sex field

        // Handle image upload if a file is sent
         $image_url = null;
         // Check if image data is sent in base64 (from frontend JS)
          $base64Image = $requestData['image_data'] ?? null;

         if ($base64Image) {
              // Assuming upload logic exists and returns a URL
               try {
                   // The saveImage function should handle decoding and saving
                   // Replace 'saveImage' with your actual image saving function
                   // It needs to handle the base64 string
                   $image_url = saveImage($base64Image, $idTag, $userId); // Pass userId and idTag for unique naming
                   if (!$image_url) {
                        // Handle image saving error if the function returns false/null on failure
                        error_log("Image saving failed for new animal: " . $idTag);
                       // Optionally, continue without image or throw an error
                   }
               } catch (Exception $e) {
                   error_log("Exception during image saving for new animal: " . $e->getMessage());
                   // Optionally, continue without image or throw an error
               }
         }


        $userId = $_SESSION['user_id'] ?? null; // Get user ID from session

         // --- Basic Validation ---
         if (empty($userId)) {
              http_response_code(401); // Unauthorized
              throw new Exception('User not logged in.');
         }

        // Prepare SQL statement for inserting into livestock table
        $sql = "INSERT INTO livestock (idTag, type, breed, birthDate, ageYears, ageMonths, weight, weightUnit, status, location, notes, image_url, user_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"; // NOW() sets current timestamp

        $stmt = $pdo->prepare($sql);

        // Execute the statement
        $stmt->execute([
            $idTag, $type, $breed, $birthDate, $ageYears, $ageMonths,
            $weight, $weightUnit, $status, $location, $notes, $image_url, $userId
        ]);

        // Get the ID of the newly inserted animal
       $newAnimalId = $pdo->lastInsertId();

        // --- ADD ACTIVITY: Log 'Added' activity for the new animal ---
        error_log("Attempting to log 'Added' activity for new animal ID: " . $newAnimalId); // LOG 1: Attempting to log
        // --- ADD ACTIVITY: Log 'Added' activity for the new animal ---
       
        try {
            $activityType = 'Added';
            $activityNotes = 'Animal added to the system.';
            $activitySql = "INSERT INTO activities (animal_id, user_id, type, notes, timestamp) VALUES (?, ?, ?, ?, NOW())";
            $activityStmt = $pdo->prepare($activitySql);

            // Check if prepare was successful (optional but good debug practice)
            if ($activityStmt === false) {
                $errorInfo = $pdo->errorInfo();
                error_log("Activity INSERT prepare failed: " . $errorInfo[2]);
                 // Still try to execute to see if execute gives a better error, or handle prepare error explicitly
            }


            $activityStmt->execute([$newAnimalId, $userId, $activityType, $activityNotes]);

            // Check if execute was successful and rows were affected (optional but good debug practice)
             if ($activityStmt->rowCount() > 0) {
                 error_log("Successfully logged 'Added' activity for new animal ID: " . $newAnimalId); // LOG 2: Success
             } else {
                 error_log("Activity INSERT execute affected 0 rows for new animal ID: " . $newAnimalId . ". Check query/data."); // LOG 3: Execute OK, but 0 rows affected
                  // Get error info even on successful execute but 0 rows (might be a warning)
                   $errorInfo = $activityStmt->errorInfo();
                   if ($errorInfo[0] !== '00000') { // '00000' is success
                        error_log("Activity INSERT execute non-success SQLSTATE for new animal ID " . $newAnimalId . ": " . print_r($errorInfo, true));
                   }
             }


        } catch (PDOException $e) {
            error_log("PDO Exception caught while logging 'Added' activity for new animal ID " . $newAnimalId . ": " . $e->getMessage()); // LOG 4: PDO Exception
            // Continue execution even if activity logging fails
        } catch (Exception $e) {
             error_log("General Exception caught while logging 'Added' activity for new animal ID " . $newAnimalId . ": " . $e->getMessage()); // LOG 5: General Exception
             // Continue execution
        }


        $response = ['success' => true, 'message' => 'Animal added successfully!', 'animalId' => $newAnimalId];
        break;

    case 'update_animal':
        // --- Update an existing animal ---
        // This action expects JSON data
         $contentType = trim(explode(';', $_SERVER['CONTENT_TYPE'])[0]);
         if ($contentType !== 'application/json') {
              http_response_code(400); // Bad Request
              throw new Exception('Invalid Content-Type for update_animal. Expected application/json.');
         }

        $jsonInput = file_get_contents('php://input');
        $requestData = json_decode($jsonInput, true);

        if ($requestData === null) {
            http_response_code(400); // Bad Request
            throw new Exception('Invalid JSON data received');
        }

        // Validate required fields (adjust as per your form)
         $requiredFields = ['id', 'idTag', 'type', 'birthDate', 'ageYears', 'ageMonths', 'weight', 'weightUnit', 'status', 'location', 'notes']; // 'id' is required for update

        foreach ($requiredFields as $field) {
            if (!isset($requestData[$field])) {
                http_response_code(400); // Bad Request
                throw new Exception("Missing required field for update: $field");
            }
        }

        // Get data from the request
        $animalId = $requestData['id']; // Get the server ID for update
        $idTag = $requestData['idTag'];
        $type = $requestData['type'];
        $birthDate = !empty($requestData['birthDate']) ? $requestData['birthDate'] : null; // Allow null for birthDate
        $ageYears = $requestData['ageYears'];
        $ageMonths = $requestData['ageMonths'];
        $weight = $requestData['weight'];
        $weightUnit = $requestData['weightUnit'];
        $status = $requestData['status'];
        $location = $requestData['location'];
        $notes = $requestData['notes'];
        $breed = $requestData['breed'] ?? null; // Breed might be optional
        $sex = $requestData['sex'] ?? null; // Assuming you might add sex field

         // Handle image upload if a new file is sent or existing is removed
          $image_url = $requestData['existing_image_url'] ?? null; // Start with existing URL
          $base64Image = $requestData['image_data'] ?? null; // New image data

         if ($base64Image) {
              // If new image data is sent, save it and get the new URL
               try {
                   // The saveImage function should handle decoding and saving
                   // Replace 'saveImage' with your actual image saving function
                   // It needs to handle the base64 string and potentially delete the old image
                   $new_image_url = saveImage($base64Image, $idTag, $userId, $image_url); // Pass old URL to delete if needed
                   if ($new_image_url) {
                       $image_url = $new_image_url; // Use the new URL
                   } else {
                       // Handle image saving error - keep existing or set to null
                        error_log("Image saving failed during update for animal: " . $idTag);
                       // Decide how to handle: keep old image, set to null, or throw error
                       // For now, let's keep the existing URL if saving fails
                   }
               } catch (Exception $e) {
                   error_log("Exception during image saving update for animal: " . $e->getMessage());
                    // Decide how to handle exception: keep old image, set to null, or throw error
                    // For now, let's keep the existing URL on exception
               }
         } else if (isset($requestData['image_data']) && $requestData['image_data'] === '') {
             // If image_data is explicitly sent as empty string, it means remove the image
              if ($image_url) {
                   // Assuming a deleteImage function exists
                   // Replace 'deleteImage' with your actual image deletion function
                   deleteImage($image_url); // Delete the old image file
              }
             $image_url = null; // Set image_url to null in database
         }


        $userId = $_SESSION['user_id'] ?? null; // Get user ID from session

         // --- Basic Validation ---
         if (empty($userId)) {
              http_response_code(401); // Unauthorized
              throw new Exception('User not logged in.');
         }


        // --- FETCH CURRENT ANIMAL DATA BEFORE UPDATE ---
         $currentAnimalSql = "SELECT * FROM livestock WHERE id = ? AND user_id = ?";
         $currentAnimalStmt = $pdo->prepare($currentAnimalSql);
         $currentAnimalStmt->execute([$animalId, $userId]);
         $currentAnimal = $currentAnimalStmt->fetch(PDO::FETCH_ASSOC);
         $currentAnimalStmt->closeCursor();

         if (!$currentAnimal) {
              http_response_code(404); // Not Found
              throw new Exception("Animal with ID " . $animalId . " not found or does not belong to this user.");
         }

        
        // --- ADD ACTIVITY: Check for changes and log 'Updated' activities ---
        error_log("Attempting to check for changes and log 'Updated' activity for animal ID: " . $animalId); // LOG 6: Attempting check/log
        
        try {
            $activityNotes = []; // Array to store notes about changes

            // Compare fields to detect changes
             // --- Add logging here to see the values being compared ---
             // error_log("Comparing Status: Current=" . ($currentAnimal['status'] ?? 'NULL') . ", New=" . ($status ?? 'NULL'));
             // error_log("Comparing Weight: Current=" . ($currentAnimal['weight'] ?? 'NULL') . ", New=" . ($weight ?? 'NULL'));
             // error_log("Comparing Location: Current=" . ($currentAnimal['location'] ?? 'NULL') . ", New=" . ($location ?? 'NULL'));
             // error_log("Comparing Notes: Current=" . (substr($currentAnimal['notes'] ?? '', 0, 50) . '...') . ", New=" . (substr($notes ?? '', 0, 50) . '...'));


            if (($currentAnimal['status'] ?? null) !== ($status ?? null)) { // Use null coalesce for comparison safety
                 $activityNotes[] = "Status changed from '" . ($currentAnimal['status'] ?? 'N/A') . "' to '" . ($status ?? 'N/A') . "'.";
             }
             // Compare weight and unit together
             if (($currentAnimal['weight'] ?? null) !== ($weight ?? null) || ($currentAnimal['weightUnit'] ?? null) !== ($weightUnit ?? null)) {
                 $activityNotes[] = "Weight changed from '" . ($currentAnimal['weight'] ?? '-') . " " . ($currentAnimal['weightUnit'] ?? '') . "' to '" . ($weight ?? '-') . " " . ($weightUnit ?? '') . "'.";
             }
              if (($currentAnimal['location'] ?? null) !== ($location ?? null)) {
                  $activityNotes[] = "Location changed from '" . ($currentAnimal['location'] ?? 'N/A') . "' to '" . ($location ?? 'N/A') . "'.";
              }
               // Only log notes update if the new notes are different and not empty
               if (!empty($notes) && (($currentAnimal['notes'] ?? '') !== $notes) ) {
                    $activityNotes[] = "Notes updated.";
               }
              // Add checks for other relevant fields you want to log activities for (e.g., breed, sex, image_url)
              // if (($currentAnimal['breed'] ?? null) !== ($breed ?? null)) { $activityNotes[] = "Breed changed..."; }
              // if (($currentAnimal['sex'] ?? null) !== ($sex ?? null)) { $activityNotes[] = "Sex changed..."; }
              // Image URL change might be logged by the image handling function itself or here

            // If any changes were detected, log a single 'Updated' activity with combined notes
            if (!empty($activityNotes)) {
                error_log("Changes detected for animal ID " . $animalId . ". Logging 'Updated' activity. Notes: " . implode(" ", $activityNotes)); // LOG 7: Changes detected

                $activityType = 'Updated';
                $combinedNotes = implode(" ", $activityNotes); // Join notes

                $activitySql = "INSERT INTO activities (animal_id, user_id, type, notes, timestamp) VALUES (?, ?, ?, ?, NOW())";
                $activityStmt = $pdo->prepare($activitySql);

                // Check if prepare was successful (optional but good debug practice)
                if ($activityStmt === false) {
                     $errorInfo = $pdo->errorInfo();
                     error_log("Activity INSERT prepare failed during update for animal ID " . $animalId . ": " . $errorInfo[2]);
                }


                $activityStmt->execute([$animalId, $userId, $activityType, $combinedNotes]);

                 // Check if execute was successful and rows were affected (optional but good debug practice)
                 if ($activityStmt->rowCount() > 0) {
                     error_log("Successfully logged 'Updated' activity for animal ID: " . $animalId); // LOG 8: Success
                 } else {
                     error_log("Activity INSERT execute affected 0 rows during update for animal ID: " . $animalId . ". Check query/data."); // LOG 9: Execute OK, but 0 rows affected
                      // Get error info even on successful execute but 0 rows (might be a warning)
                       $errorInfo = $activityStmt->errorInfo();
                       if ($errorInfo[0] !== '00000') { // '00000' is success
                            error_log("Activity INSERT execute non-success SQLSTATE during update for animal ID " . $animalId . ": " . print_r($errorInfo, true));
                       }
                 }

            } else {
                 error_log("No relevant changes detected for animal ID: " . $animalId . ". No activity logged."); // LOG 10: No changes detected
            }

        } catch (PDOException $e) {
            error_log("PDO Exception caught while logging 'Updated' activity for animal ID " . $animalId . ": " . $e->getMessage()); // LOG 11: PDO Exception
            // Continue execution
        } catch (Exception $e) {
             error_log("General Exception caught while logging 'Updated' activity for animal ID " . $animalId . ": " . $e->getMessage()); // LOG 12: General Exception
             // Continue execution
        }


        // Prepare SQL statement for updating livestock table
        $sql = "UPDATE livestock SET idTag = ?, type = ?, breed = ?, birthDate = ?, ageYears = ?, ageMonths = ?, weight = ?, weightUnit = ?, status = ?, location = ?, notes = ?, image_url = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?"; // Ensure update is for the correct user

        $stmt = $pdo->prepare($sql);

        // Execute the statement
        $stmt->execute([
            $idTag, $type, $breed, $birthDate, $ageYears, $ageMonths,
            $weight, $weightUnit, $status, $location, $notes, $image_url,
            $animalId, $userId // Where clause parameters
        ]);

        // Check if the update was successful (optional, but good practice)
        $rowsAffected = $stmt->rowCount();
        if ($rowsAffected === 0 && !empty($activityNotes)) {
             // If no rows were affected in the livestock table but activities were logged,
             // it might mean the livestock data was identical, but we logged an activity
             // based on the *comparison* which might be slightly different from the exact DB value.
             // Or the animal ID/user ID didn't match.
             // For simplicity, we'll assume if activityNotes is not empty, the update was logically successful from the user's perspective
             // based on the values they submitted. If rowsAffected === 0 and activityNotes is empty, nothing changed.
        } else if ($rowsAffected > 0) {
             // Update was successful
             error_log("Livestock record updated successfully for ID: " . $animalId);
        } else {
             // Animal not found or user mismatch during update itself
             // This case should ideally be caught by the initial fetch check,
             // but adding a log here is extra safety.
              error_log("Warning: Livestock update statement affected 0 rows for ID: " . $animalId . ". Check ID/User ID.");
               // Optionally set a response message indicating no changes were made if rowsAffected is 0 and activityNotes is empty
        }


        $response = ['success' => true, 'message' => 'Animal updated successfully!'];
        break;

    // ... (Other cases like get_animal_details, delete_animal, etc.) ...

    // --- Add helper function for image saving (assuming base64 input) ---
     // You need to implement the actual file saving logic here
     // This is a placeholder function - replace with your actual implementation
     function saveImage($base64Image, $idTag, $userId, $oldImageUrl = null) {
         // Decode the base64 string
         $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
         if ($data === false) {
              error_log("Base64 decode failed for image.");
              return false; // Decoding failed
         }

         // Determine file extension (e.g., png, jpg, jpeg)
         $finfo = finfo_open(FILEINFO_MIME_TYPE);
         $mime_type = finfo_buffer($finfo, $data);
         finfo_close($finfo);

         $extension = 'jpg'; // Default extension
         if ($mime_type == 'image/png') {
             $extension = 'png';
         } elseif ($mime_type == 'image/gif') {
             $extension = 'gif';
         } elseif ($mime_type == 'image/jpeg') {
             $extension = 'jpg'; // Use jpg for jpeg
         } else {
              error_log("Unsupported image mime type: " . $mime_type);
              return false; // Unsupported type
         }

         // Define upload directory (relative to livestock_api.php)
         $uploadDir = '../uploads/livestock/'; // Adjust path as needed
         if (!is_dir($uploadDir)) {
             mkdir($uploadDir, 0777, true); // Create directory if it doesn't exist
         }

         // Create a unique filename (e.g., user_id-idTag-timestamp.extension)
         // Sanitize idTag to be filesystem safe
         $safeIdTag = preg_replace("/[^a-zA-Z0-9-_.]/", "", $idTag);
         $filename = $userId . '-' . $safeIdTag . '-' . time() . '.' . $extension;
         $filePath = $uploadDir . $filename;

         // Save the file
         if (file_put_contents($filePath, $data) !== false) {
              // If an old image URL was provided, attempt to delete the old file
               if ($oldImageUrl) {
                   try {
                       deleteImage($oldImageUrl); // Call the delete function
                   } catch (Exception $e) {
                       error_log("Error deleting old image: " . $e->getMessage());
                       // Continue even if old image deletion fails
                   }
               }
             // Return the URL relative to the webroot
             return 'uploads/livestock/' . $filename; // Adjust URL path as needed for web access

         } else {
              error_log("Failed to save image file to: " . $filePath);
              return false; // File saving failed
         }
     }

     // --- Add helper function for image deletion ---
      // You need to implement the actual file deletion logic here
      // This is a placeholder function - replace with your actual implementation
      function deleteImage($imageUrl) {
          // Assuming the image URL is relative to the webroot like 'uploads/livestock/...'
          $filePath = '../' . $imageUrl; // Adjust path to be relative to livestock_api.php

          if (file_exists($filePath) && is_file($filePath)) {
              if (unlink($filePath)) {
                  error_log("Successfully deleted old image: " . $filePath);
                  return true; // Deletion successful
              } else {
                  error_log("Failed to delete old image file: " . $filePath);
                  // Depending on requirements, you might throw an exception or return false
                  return false; // Deletion failed
              }
          } else {
              // File does not exist, which is okay for deletion
              error_log("Attempted to delete non-existent image file: " . $filePath);
              return false; // File not found
          }
      }

        case 'get_livestock':
            // --- Fetch all livestock for the current user ---
            // This action expects JSON data (filters)
             if ($contentType !== 'application/json') {
                  http_response_code(400);
                  throw new Exception('Invalid Content-Type for get_livestock. Expected application/json.');
             }

            $search = $requestData['search'] ?? '';
            $type = $requestData['type'] ?? 'all';
            $status = $requestData['status'] ?? 'all';
            $sortBy = $requestData['sortBy'] ?? 'id-asc'; // Default sort

            $sql = "SELECT * FROM livestock WHERE user_id = ?";
            $params = [$userId];

            // Add filters
            if ($search) {
                $sql .= " AND (idTag LIKE ? OR type LIKE ? OR breed LIKE ? OR location LIKE ? OR status LIKE ? OR notes LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            if ($type !== 'all') {
                $sql .= " AND type = ?";
                $params[] = $type;
            }
            if ($status !== 'all') {
                $sql .= " AND status = ?";
                $params[] = $status;
            }

            // Add sorting
            $orderBy = '';
            switch ($sortBy) {
                case 'id-asc': $orderBy = 'idTag ASC'; break;
                case 'id-desc': $orderBy = 'idTag DESC'; break;
                case 'age-asc': $orderBy = 'ageYears ASC, ageMonths ASC'; break;
                case 'age-desc': $orderBy = 'ageYears DESC, ageMonths DESC'; break;
                case 'weight-asc': $orderBy = 'weight ASC'; break;
                case 'weight-desc': $orderBy = 'weight DESC'; break;
                case 'updatedAt-asc': $orderBy = 'updatedAt ASC'; break;
                case 'updatedAt-desc': $orderBy = 'updatedAt DESC'; break;
                default: $orderBy = 'idTag ASC'; break; // Default
            };
            $sql .= " ORDER BY " . $orderBy;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $livestock = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $response = [
                'success' => true,
                'livestock' => $livestock
            ];
            break;

         // livestock_api.php script තුළ
            // switch ($action) Block එක තුළ
            case 'get_animal_details':
                // --- Fetch details for a single animal ---
                // This action expects JSON data (animalId and userId)
                // Check Content-Type: Must be application/json for this action
                $contentType = trim(explode(';', $_SERVER['CONTENT_TYPE'])[0]);
                if ($contentType !== 'application/json') {
                        http_response_code(400); // Bad Request
                        throw new Exception('Invalid Content-Type for get_animal_details. Expected application/json.');
                }

                // Read JSON input
                $jsonInput = file_get_contents('php://input');
                $requestData = json_decode($jsonInput, true);

                // Validate input data
                if ($requestData === null) {
                    http_response_code(400); // Bad Request
                    throw new Exception('Invalid JSON data received');
                }


                $animalId = $requestData['animalId'] ?? null;
                $userId = $requestData['userId'] ?? null; // Get user ID from request data (should also be in session)

                // --- Basic Validation ---
                if (!$animalId) {
                    http_response_code(400); // Bad Request
                    throw new Exception('Missing animal ID for details.');
                }
                // Although user ID is checked globally, validate it here too for this specific action
                if (empty($userId)) {
                    http_response_code(401); // Unauthorized
                    throw new Exception('User ID not provided in request or user not logged in.');
                }


                // --- Fetch animal details ---
                // Ensure the animal belongs to the current user
                $sql = "SELECT * FROM livestock WHERE id = ? AND user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$animalId, $userId]);
                $animal = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($animal) {
                    // --- Fetch activities for this animal ---
                    try {
                        // Assuming 'activities' table and 'animal_id' column exist and are correct.
                        // This query fetches all activities linked to this animal ID, ordered by timestamp.
                        $activitySql = "SELECT * FROM activities WHERE animal_id = ? ORDER BY timestamp DESC";
                        $activityStmt = $pdo->prepare($activitySql);
                        $activityStmt->execute([$animalId]);
                        $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
                        $activityStmt->closeCursor();
                        // Add the fetched activities as an 'activities' key to the animal data array
                        $animal['activities'] = $activities;
                    } catch (PDOException $e) {
                        // --- Handle Database Errors Gracefully for Activities ---
                        error_log("Database error fetching activities for animal ID " . $animalId . ": " . $e->getMessage());

                        // Check for specific errors indicating table or column issues
                        if (strpos($e->getMessage(), "Unknown column 'animal_id'") !== false ||
                            strpos($e->getMessage(), "Base table or view not found") !== false ||
                            strpos($e->getMessage(), "doesn't exist") !== false // Common error message for missing table
                            ) {
                                // If the error is likely due to the activities table structure or existence,
                                // return an empty activities array gracefully instead of failing the whole request.
                                $animal['activities'] = []; // Return empty array
                                // Optionally log a warning about the potential DB config issue
                                error_log("Warning: Potential activities table configuration issue detected for animal ID " . $animalId);

                        } else {
                            // If it's a different kind of database error (e.g., syntax error, connection issue),
                            // re-throw the exception to be caught by the main try-catch block
                            throw $e;
                        }
                    }

                    // --- Prepare the successful response ---
                    $response = [
                        'success' => true,
                        'animal' => $animal // Return the animal object which now includes the 'activities' key
                    ];

                } else {
                    // --- Handle case where animal is not found for the user ---
                    $response = [
                        'success' => false,
                        'message' => 'Animal not found or you do not have permission to view it.'
                    ];
                    http_response_code(404); // Not Found
                }
                break; // End of case 'get_animal_details'


        case 'add_livestock':
            // --- Add a new livestock record ---
             // This action expects FormData ($_POST and $_FILES)
             if ($contentType !== 'multipart/form-data' && $contentType !== 'application/x-www-form-urlencoded') {
                  http_response_code(400);
                   throw new Exception('Invalid Content-Type for add_livestock. Expected multipart/form-data.');
             }

            $animalData = $requestData; // Use data from $_POST

            if (!is_array($animalData) || !isset($animalData['idTag'], $animalData['type'])) {
                http_response_code(400); // Bad Request
                throw new Exception('Invalid or incomplete animal data for addition');
            }

            // Check for duplicate idTag for this user (Optional server-side check)
             $stmtCheck = $pdo->prepare("SELECT id FROM livestock WHERE user_id = ? AND idTag = ?");
             $stmtCheck->execute([$userId, $animalData['idTag']]);
             if ($stmtCheck->fetch()) {
                  http_response_code(409); // Conflict
                  throw new Exception('Animal with this ID Tag already exists.');
             }


            $imageUrl = null; // Variable to store the image URL/path
            // Handle image upload - Check $_FILES
            if (isset($_FILES['animalImage']) && $_FILES['animalImage']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['animalImage']['tmp_name'];
                $fileName = $_FILES['animalImage']['name'];
                $fileSize = $_FILES['animalImage']['size'];
                $fileType = $_FILES['animalImage']['type'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                // Sanitize file name and create a unique name
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                // Specify the upload directory (e.g., relative to your API file)
                // Ensure this path is correct and writable by the web server
                $uploadFileDir = __DIR__ . '/../uploads/livestock_images/';
                $dest_path = $uploadFileDir . $newFileName;

                 // Ensure the upload directory exists
                 if (!is_dir($uploadFileDir)) {
                      // Attempt to create the directory recursively with appropriate permissions
                      if (!mkdir($uploadFileDir, 0775, true)) { // 0775 or 0755 depending on server config
                           error_log("Failed to create upload directory: " . $uploadFileDir);
                           throw new Exception('Failed to create upload directory.');
                      }
                 }

                // Check allowed file types and size
                $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
                if (in_array($fileExtension, $allowedfileExtensions) && $fileSize <= 5 * 1024 * 1024) { // Max 5MB
                    if (move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Store the relative path or URL to the image in the database
                        // Using a path relative to the webroot is common
                        $imageUrl = '../uploads/livestock_images/' . $newFileName; // Adjust based on your web server config
                    } else {
                        error_log("Error moving uploaded file: " . $_FILES['animalImage']['error']);
                        throw new Exception('Error saving the uploaded file.');
                    }
                } else {
                    throw new Exception('Invalid file type or size for image upload.');
                }
            }


            // Insert the new livestock record
            $sql = "INSERT INTO livestock (user_id, idTag, type, breed, ageYears, ageMonths, birthDate, weight, weightUnit, status, location, notes, image_url, createdAt, updatedAt)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"; // Use NOW() for server timestamps
            $stmt = $pdo->prepare($sql);

            // Prepare data for binding - handle potential missing fields using ?? operator for null/undefined
            $bindParams = [
                $userId,
                $animalData['idTag'] ?? null,
                $animalData['type'] ?? null,
                $animalData['breed'] ?? null,
                $animalData['ageYears'] ?? 0,
                $animalData['ageMonths'] ?? 0,
                empty($animalData['birthDate']) ? null : $animalData['birthDate'], // Handle empty date string
                $animalData['weight'] ?? 0.0,
                $animalData['weightUnit'] ?? 'kg',
                $animalData['status'] ?? 'healthy',
                $animalData['location'] ?? null,
                $animalData['notes'] ?? null,
                $imageUrl // Use the generated image URL/path here (will be null if no image uploaded)
            ];

            if ($stmt->execute($bindParams)) {
                $newServerId = $pdo->lastInsertId();
                $response = [
                    'success' => true,
                    'message' => 'Livestock added successfully',
                    'id' => $newServerId // Return the new server ID
                ];
            } else {
                // If DB insert fails after saving image, consider deleting the saved image file
                if ($imageUrl && file_exists(__DIR__ . '/../' . $imageUrl)) { // Use absolute path to check
                    unlink(__DIR__ . '/../' . $imageUrl);
                }
                 error_log("Database insert failed: " . implode(" ", $stmt->errorInfo()));
                throw new Exception('Database insert failed.');
            }
            $stmt->closeCursor();
            break;

        case 'update_livestock':
            // --- Update an existing livestock record ---
            // This action expects FormData ($_POST and $_FILES)
             if ($contentType !== 'multipart/form-data' && $contentType !== 'application/x-www-form-urlencoded') {
                  http_response_code(400);
                   throw new Exception('Invalid Content-Type for update_livestock. Expected multipart/form-data.');
             }

             $animalData = $requestData; // Use data from $_POST
            $animalId = $animalData['id'] ?? null; // Expecting server ID for update

            if (!is_array($animalData) || !$animalId || !isset($animalData['idTag'], $animalData['type'], $animalData['status'])) {
                 http_response_code(400); // Bad Request
                throw new Exception('Invalid or incomplete animal data for update');
            }

             // Check if the animal belongs to the current user before updating
             $stmtCheckOwnership = $pdo->prepare("SELECT id FROM livestock WHERE id = ? AND user_id = ?");
             $stmtCheckOwnership->execute([$animalId, $userId]);
             if (!$stmtCheckOwnership->fetch()) {
                 http_response_code(403); // Forbidden
                 throw new Exception('You do not have permission to edit this animal.');
             }
             $stmtCheckOwnership->closeCursor();

             // Check for duplicate idTag if the idTag is being changed (Optional server-side check)
              if (isset($animalData['idTag'])) {
                   $stmtCheckDuplicate = $pdo->prepare("SELECT id FROM livestock WHERE user_id = ? AND idTag = ? AND id != ?");
                   $stmtCheckDuplicate->execute([$userId, $animalData['idTag'], $animalId]);
                   if ($stmtCheckDuplicate->fetch()) {
                        http_response_code(409); // Conflict
                        throw new Exception('Animal with this ID Tag already exists.');
                   }
                   $stmtCheckDuplicate->closeCursor();
              }

             $imageUrl = $animalData['existing_image_url'] ?? null; // Start with the existing image URL from hidden field


             // Handle image upload - Check $_FILES
             if (isset($_FILES['animalImage']) && $_FILES['animalImage']['error'] === UPLOAD_ERR_OK) {
                 $fileTmpPath = $_FILES['animalImage']['tmp_name'];
                 $fileName = $_FILES['animalImage']['name'];
                 $fileSize = $_FILES['animalImage']['size'];
                 $fileType = $_FILES['animalImage']['type'];
                 $fileNameCmps = explode(".", $fileName);
                 $fileExtension = strtolower(end($fileNameCmps));

                 $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                 $uploadFileDir = __DIR__ . '/../uploads/livestock_images/'; // Adjust this path
                 $dest_path = $uploadFileDir . $newFileName;

                 if (!is_dir($uploadFileDir)) {
                      if (!mkdir($uploadFileDir, 0775, true)) {
                           error_log("Failed to create upload directory during update: " . $uploadFileDir);
                           throw new Exception('Failed to create upload directory for update.');
                      }
                 }


                 $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg');
                 if (in_array($fileExtension, $allowedfileExtensions) && $fileSize <= 5 * 1024 * 1024) {
                     if (move_uploaded_file($fileTmpPath, $dest_path)) {
                         // New image uploaded successfully
                         $newImageUrl = '../uploads/livestock_images/' . $newFileName; // Relative path

                         // Delete the old image file if a new one is uploaded and an old one existed
                          if (!empty($imageUrl) && file_exists(__DIR__ . '/../' . $imageUrl)) {
                              // Check if the old file is not the same as the new one (unlikely but safe)
                               if (__DIR__ . '/../' . $imageUrl !== $dest_path) {
                                    unlink(__DIR__ . '/../' . $imageUrl);
                               }
                          }
                          $imageUrl = $newImageUrl; // Update $imageUrl to the new image path

                     } else {
                        error_log("Error moving new uploaded file during update: " . $_FILES['animalImage']['error']);
                        throw new Exception('Error saving the new uploaded file.');
                     }
                 } else {
                     throw new Exception('Invalid new file type or size for image upload.');
                 }
             } else {
                 // No new file uploaded. Check if the 'remove_image' flag was sent (if you implement that)
                 // For now, if no new file and existing_image_url was sent, $imageUrl already holds it.
                 // If you add a 'remove_image' checkbox/flag, handle it here.
                 // e.g., if (isset($animalData['remove_image']) && $animalData['remove_image'] === 'on') { $imageUrl = null; /* delete old file */ }
             }


            $sql = "UPDATE livestock SET
                    idTag = ?, type = ?, breed = ?, ageYears = ?, ageMonths = ?, birthDate = ?,
                    weight = ?, weightUnit = ?, status = ?, location = ?, notes = ?, image_url = ?, updatedAt = NOW()
                    WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);

            $bindParams = [
                $animalData['idTag'] ?? null,
                $animalData['type'] ?? null,
                $animalData['breed'] ?? null,
                $animalData['ageYears'] ?? 0,
                $animalData['ageMonths'] ?? 0,
                empty($animalData['birthDate']) ? null : $animalData['birthDate'],
                $animalData['weight'] ?? 0.0,
                $animalData['weightUnit'] ?? 'kg',
                $animalData['status'] ?? 'healthy',
                $animalData['location'] ?? null,
                $animalData['notes'] ?? null,
                $imageUrl, // Use the potentially new or existing image URL (or null)
                $animalId,
                $userId
            ];

            if ($stmt->execute($bindParams)) {
                if ($stmt->rowCount() > 0) {
                     $response = ['success' => true, 'message' => 'Livestock updated successfully'];
                } else {
                     $response = ['success' => true, 'message' => 'Livestock record found, but no changes applied.'];
                }
            } else {
                 // If DB update fails after saving new image, consider deleting the new saved image file
                  if (isset($newImageUrl) && file_exists(__DIR__ . '/../' . $newImageUrl)) {
                       unlink(__DIR__ . '/../' . $newImageUrl);
                  }
                 error_log("Database update failed: " . implode(" ", $stmt->errorInfo()));
                throw new Exception('Database update failed.');
            }
            $stmt->closeCursor();

            break;

        case 'delete_livestock':
            // --- Delete a livestock record ---
             // This action expects JSON data (animalId)
              if ($contentType !== 'application/json') {
                   http_response_code(400);
                   throw new Exception('Invalid Content-Type for delete_livestock. Expected application/json.');
              }

            $animalId = $requestData['animalId'] ?? null;
            // userId check is already done before the switch

            if (!$animalId) {
                http_response_code(400); // Bad Request
                throw new Exception('Missing animal ID for deletion');
            }

             // Check if the animal belongs to the current user before deleting
             $stmtCheckOwnership = $pdo->prepare("SELECT id, image_url FROM livestock WHERE id = ? AND user_id = ?");
             $stmtCheckOwnership->execute([$animalId, $userId]);
             $animalToDelete = $stmtCheckOwnership->fetch(PDO::FETCH_ASSOC); // Fetch to get image_url
             $stmtCheckOwnership->closeCursor();

             if (!$animalToDelete) {
                 http_response_code(403); // Forbidden or 404 Not Found depending on desired behavior
                 throw new Exception('You do not have permission to delete this animal or animal not found.');
             }

             // Optional: Delete related activities first if ON DELETE CASCADE is not set up on activities table
             // $stmtDeleteActivities = $pdo->prepare("DELETE FROM activities WHERE animal_id = ?");
             // $stmtDeleteActivities->execute([$animalId]);


            // Perform the deletion
            $sql = "DELETE FROM livestock WHERE id = ? AND user_id = ?";
            $stmt = $pdo->prepare($sql);

            if ($stmt->execute([$animalId, $userId])) {
                // Check if any rows were affected to confirm deletion
                if ($stmt->rowCount() > 0) {
                     // Delete the associated image file after successful database deletion
                     if (!empty($animalToDelete['image_url']) && file_exists(__DIR__ . '/../' . $animalToDelete['image_url'])) {
                         unlink(__DIR__ . '/../' . $animalToDelete['image_url']);
                         error_log("Deleted image file: " . $animalToDelete['image_url']);
                     }
                    $response = ['success' => true, 'message' => 'Livestock deleted successfully'];
                } else {
                    $response = ['success' => true, 'message' => 'Livestock not found or already deleted.'];
                     http_response_code(200); // OK, but indicate it wasn't found
                }
            } else {
                 error_log("Database delete failed: " . implode(" ", $stmt->errorInfo()));
                throw new Exception('Database delete failed.');
            }
            $stmt->closeCursor();
            break;

        // Add other API actions here if needed (e.g., add_activity)

        default:
            // Unknown action requested
            http_response_code(400); // Bad Request
            throw new Exception('Unknown action: ' . htmlspecialchars($action));
            break;
    }

    // Send the final JSON response
    echo json_encode($response);

} catch (Exception $e) {
    // Log the error on the server side
    error_log('Livestock API Error: ' . $e->getMessage());

    // Set an appropriate HTTP status code if not already set
    if (!http_response_code() || http_response_code() < 400) {
         http_response_code(500); // Internal Server Error by default for uncaught exceptions
    }

    // Send error response to the client
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);

}

    
// The exit() is not strictly needed after echo json_encode in an API context,
// but it prevents any trailing whitespace or output.
// exit(); // Removed as per original code structure.

?>