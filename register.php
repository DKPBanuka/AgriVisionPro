<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['logged_in'])) {
    header("Location: dashboard.php");
    exit();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Change to your actual password
define('DB_NAME', 'agri_vision_pro');

// Handle registration
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        try {
            $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if username or email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = 'Username or email already exists';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password) VALUES (:full_name, :username, :email, :password)");
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->execute();

                // Get the new user ID
                $user_id = $conn->lastInsertId();

                // Create empty profile
                $stmt = $conn->prepare("INSERT INTO profiles (user_id, full_name, email) VALUES (:user_id, :full_name, :email)");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                // Set success message and auto-redirect
                $success = 'Registration successful! Redirecting to login page...';
                $_SESSION['registration_success'] = true;
                
                // JavaScript for auto-redirect
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "index.php";
                    }, 2000);
                </script>';
            }
        } catch(PDOException $e) {
            $error = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | AgriVision Pro</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --primary-light: #93c5fd;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
        }
        .card-shadow {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .input-focus:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .password-strength {
            height: 4px;
            background-color: #e5e7eb;
            margin-top: 4px;
            border-radius: 2px;
            overflow: hidden;
        }
        .password-strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 p-4">
    <div class="w-full max-w-md animate-fade-in">
        <!-- Registration Card -->
        <div class="bg-white rounded-xl card-shadow overflow-hidden">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-8 text-center text-white">
                <div class="flex justify-center mb-4">
                    <div class="bg-white/20 p-4 rounded-full backdrop-blur-sm">
                        <i class="fas fa-user-plus text-3xl text-white"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-bold">Create Your Account</h1>
                <p class="text-blue-100 mt-2">Join AgriVision Pro today</p>
            </div>
            
            <!-- Form Section -->
            <div class="p-8">
                <?php if (!empty($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800"><?= htmlspecialchars($error) ?></p>
                        </div>
                        <button onclick="this.parentElement.remove()" class="ml-auto text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded-lg animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <div>
                            <p class="text-sm font-medium text-green-800"><?= htmlspecialchars($success) ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-5">
                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="full_name" name="full_name" required
                                class="pl-10 input-focus block w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="John Doe"
                                value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-at text-gray-400"></i>
                            </div>
                            <input type="text" id="username" name="username" required
                                class="pl-10 input-focus block w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="johndoe"
                                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email" required
                                class="pl-10 input-focus block w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="john@example.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password" required
                                class="pl-10 input-focus block w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="At least 8 characters"
                                oninput="updatePasswordStrength()">
                            <div class="password-strength">
                                <div class="password-strength-fill" id="password-strength-fill"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                class="pl-10 input-focus block w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Confirm your password">
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn-primary w-full text-white py-3 px-4 rounded-lg font-medium flex items-center justify-center shadow-md hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-user-plus mr-2"></i> Create Account
                    </button>
                </form>
            </div>
            
            <!-- Footer Section -->
            <div class="bg-gray-50 px-8 py-6 border-t border-gray-200">
                <div class="text-center text-sm text-gray-600">
                    Already have an account? 
                    <a href="index.php" class="font-medium text-blue-600 hover:text-blue-800 transition-colors">
                        <i class="fas fa-sign-in-alt mr-1"></i> Sign In
                    </a>
                </div>
            </div>
        </div>
        
        <!-- App Info -->
        <div class="mt-6 text-center text-xs text-gray-500">
            <p>© <?= date('Y') ?> AgriVision Pro. All rights reserved.</p>
            <p class="mt-1">Version 2.1.0</p>
        </div>
    </div>

    <!-- Password Strength Indicator -->
    <script>
        function updatePasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('password-strength-fill');
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Complexity checks
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;
            
            // Update visual indicator
            const width = (strength / 5) * 100;
            strengthFill.style.width = width + '%';
            
            // Update color
            if (width < 40) {
                strengthFill.style.backgroundColor = '#ef4444'; // red
            } else if (width < 70) {
                strengthFill.style.backgroundColor = '#f59e0b'; // amber
            } else {
                strengthFill.style.backgroundColor = '#10b981'; // emerald
            }
        }
    </script>
</body>
</html>