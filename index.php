<?php
session_start();

// At the top of index.php, after session_start()
if (isset($_SESSION['registration_success'])) {
    $success = 'Registration successful! Please log in.';
    unset($_SESSION['registration_success']);
}

// Redirect if already logged in
if (isset($_SESSION['logged_in'])) {
    header("Location: dashboard.php");
    exit();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'agri_vision_pro');

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username OR email = :username LIMIT 1");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['logged_in'] = true;

                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = 'Invalid credentials';
                }
            } else {
                $error = 'Invalid credentials';
            }
        } catch(PDOException $e) {
            $error = 'Database error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriVision Pro | Login</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary:rgb(16, 104, 205);
            --primary-dark:rgb(15, 80, 155);
            --secondary: #F59E0B;
        }
        body {
            background-color: #F8FAFC;
            font-family: 'Inter', sans-serif;
        }
        .card-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .btn-primary {
            background-color: var(--primary);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }
        .input-focus:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(23, 96, 192, 0.2);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <div class="w-full max-w-md">
        <!-- Login Card -->
        <div class="bg-white rounded-xl card-shadow overflow-hidden">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-400 to-blue-700 p-6 text-center text-white">
            <div class="w-12 h-12 flex items-center justify-center mx-auto">
                <img src="./images/logo.png" alt="App Logo" class="h-10 w-10 object-contain">
            </div>
                <h1 class="text-2xl font-bold">AgriVision Pro</h1>
                <p class="text-sm opacity-90 mt-1">Smart Farming Management System</p>
            </div>
            
            <!-- Form Section -->
            <div class="p-6">
                <?php if (!empty($error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <span class="text-sm text-red-700"><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username or Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" name="username" required
                                class="pl-10 input-focus block w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none"
                                placeholder="Enter your username">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" required
                                class="pl-10 input-focus block w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none"
                                placeholder="Enter your password">
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 text-sm text-gray-600">Remember me</label>
                        </div>
                        <div class="text-sm">
                            <a href="forgot_password.php" class="font-medium text-blue-700 hover:text-blue-400">Forgot password?</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full text-white py-2 px-4 rounded-lg font-medium flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i> Sign In
                    </button>
                </form>
            </div>
            
            <!-- Footer Section -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="text-center text-sm text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="font-medium text-blue-700 hover:text-blue-400">Sign up</a>
                </div>
            </div>
        </div>
        
        <!-- App Info -->
        <div class="mt-6 text-center text-xs text-gray-500">
            <p>© <?= date('Y') ?> AgriVision Pro. All rights reserved.</p>
            <p class="mt-1">Version 2.1.0</p>
        </div>
    </div>
</body>
</html>