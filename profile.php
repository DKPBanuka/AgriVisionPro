<?php
session_start();

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$db   = 'agri_vision_pro';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Establish database connection
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Ensure user_id is an integer
$user_id = (int)$_SESSION['user_id'];

// Get user profile data
try {
    $stmt = $pdo->prepare("SELECT * FROM profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$profile) {
        $profile = [
            'full_name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'profile_picture' => ''
        ];
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error loading profile: " . $e->getMessage();
    header("Location: profile.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Basic validation
    if (empty($full_name)) {
        $_SESSION['error_message'] = "Full name is required";
        header("Location: profile.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format";
        header("Location: profile.php");
        exit();
    }

    // Phone number validation
    if (!empty($phone) && strlen($phone) > 20) {
        $_SESSION['error_message'] = "Phone number must be 20 characters or less";
        header("Location: profile.php");
        exit();
    }

    if (!empty($phone) && !preg_match('/^[0-9\+\-\(\)\s]+$/', $phone)) {
        $_SESSION['error_message'] = "Invalid phone number format";
        header("Location: profile.php");
        exit();
    }

    // Handle file upload
    $profile_picture = $profile['profile_picture'] ?? '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        // Validate image
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['profile_picture']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message'] = "Only JPG, PNG or GIF images are allowed";
            header("Location: profile.php");
            exit();
        }

        // Check file size (max 5MB)
            if ($_FILES['profile_picture']['size'] > 5242880) { // 5MB in bytes
                $_SESSION['error_message'] = "Image must be less than 5MB";
                header("Location: profile.php");
                exit();
            }

        // Set up upload directory
        $upload_dir = 'uploads/profiles/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $_SESSION['error_message'] = "Failed to create upload directory";
                header("Location: profile.php");
                exit();
            }
        }
        
        // Generate unique filename
        $file_ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('profile_', true) . '.' . $file_ext;
        $target_path = $upload_dir . $file_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
            // Delete old picture if exists
            if (!empty($profile['profile_picture']) && file_exists($profile['profile_picture'])) {
                unlink($profile['profile_picture']);
            }
            $profile_picture = $target_path;
        } else {
            $_SESSION['error_message'] = "Failed to upload image";
            header("Location: profile.php");
            exit();
        }
    }

    // Save to database
    try {
        if (isset($profile['user_id'])) {
            $stmt = $pdo->prepare("UPDATE profiles SET full_name=?, email=?, phone=?, address=?, profile_picture=? WHERE user_id=?");
            $stmt->execute([$full_name, $email, $phone, $address, $profile_picture, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO profiles (user_id, full_name, email, phone, address, profile_picture) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$user_id, $full_name, $email, $phone, $address, $profile_picture]);
        }
        
        $_SESSION['success_message'] = "Profile updated successfully!";
        header("Location: dashboard.php");
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error saving profile: " . $e->getMessage();
        header("Location: profile.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | AgriVision Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --secondary: #f59e0b;
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .profile-picture-upload {
            transition: all 0.3s ease;
        }
        .profile-picture-upload:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .input-focus:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <div class="container mx-auto py-8 px-4">
        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden animate-fade-in">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 py-6 px-8 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center justify-center space-x-4">
                    <img src="images/logo.png" alt="Logo" class="h-10 w-auto">
                        <div class="text-center w-full">
                            <h1 class="text-2xl font-bold text-center">Profile Settings</h1>
                            <p class="text-blue-100 mt-1 text-center">Manage your personal information</p>
                        </div>
                        
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-user-circle text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Notification Messages -->
            <div class="px-8 pt-4">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4 rounded-lg animate-fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($_SESSION['success_message'] ?? '') ?></p>
                            </div>
                            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-green-500 hover:text-green-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded-lg animate-fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <div>
                                <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($_SESSION['error_message'] ?? '') ?></p>
                            </div>
                            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-red-500 hover:text-red-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
            </div>
            
            <!-- Profile Form -->
            <form method="POST" enctype="multipart/form-data" class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Profile Picture Upload -->
                        <div class="profile-picture-upload">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Profile Photo</label>
                            <div class="flex items-center space-x-6">
                                <div class="relative">
                                    <?php if (!empty($profile['profile_picture']) && file_exists($profile['profile_picture'])): ?>
                                        <img src="<?= htmlspecialchars($profile['profile_picture']) ?>" 
                                             alt="Current Profile Picture" 
                                             class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md">
                                    <?php else: ?>
                                        <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center border-4 border-white shadow-md">
                                            <i class="fas fa-user text-blue-500 text-3xl"></i>
                                        </div>
                                    <?php endif; ?>
                                    <label for="profile_picture" class="absolute -bottom-2 -right-2 bg-white p-2 rounded-full shadow-md cursor-pointer hover:bg-gray-50 transition-colors">
                                        <i class="fas fa-camera text-blue-500"></i>
                                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden">
                                    </label>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Upload a clear photo of yourself</p>
                                    <p class="text-xs text-gray-500 mt-1">Max 5MB. JPG, PNG, or GIF</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Full Name -->
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400"></i>
                                </div>
                                <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>"
                                       class="pl-10 input-focus block w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" id="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" 
                                       class="pl-10 input-focus block w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                                <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                                       class="pl-10 input-focus block w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       pattern="[0-9\+\-\(\)\s]+"
                                       maxlength="20">
                                <p class="text-xs text-gray-500 mt-1">Format: +1234567890 or (123) 456-7890</p>
                            </div>
                        </div>
                        
                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 pt-3 pointer-events-none">
                                    <i class="fas fa-map-marker-alt text-gray-400"></i>
                                </div>
                                <textarea id="address" name="address" rows="3"
                                          class="pl-10 input-focus block w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="dashboard.php" class="px-4 py-2 border border-gray-300 rounded-lg font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg font-medium text-white hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 shadow-md hover:shadow-lg">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview uploaded image before submit -->
    <script>
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                const previewContainer = document.querySelector('.profile-picture-upload .relative');
                let preview = previewContainer.querySelector('img');
                
                reader.onload = function(e) {
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        const divPreview = previewContainer.querySelector('div');
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'w-24 h-24 rounded-full object-cover border-4 border-white shadow-md';
                        divPreview.replaceWith(img);
                    }
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>