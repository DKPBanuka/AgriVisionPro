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
define('DB_PASS', 'root');
define('DB_NAME', 'agri_vision_pro');

// Establish database connection
try {
    $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
                
                // Store token in database
                $updateStmt = $conn->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE email = :email");
                $updateStmt->execute([
                    ':token' => $token,
                    ':expires' => $expires,
                    ':email' => $email
                ]);
                
                // Send email (in production, use PHPMailer or similar)
                $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
                $subject = "Password Reset Request";
                $message = "Click to reset your password: $resetLink";
                $headers = "From: no-reply@agrivision.com";
                
                if (mail($email, $subject, $message, $headers)) {
                    $message = 'Password reset link sent to your email.';
                } else {
                    $error = 'Failed to send email. Please try again.';
                }
            } else {
                // Don't reveal if email doesn't exist (security)
                $message = 'If an account exists with this email, you will receive a reset link.';
            }
        } catch(PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = 'A system error occurred. Please try again later.';
        }
    }
}

// Handle password reset request
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        try {
            $conn = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = :email LIMIT 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generate reset token (in a real app, this would be more secure)
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Store token in database (in a real app, you'd have a password_resets table)
                $updateStmt = $conn->prepare("UPDATE users SET reset_token = :token, reset_expires = :expires WHERE id = :id");
                $updateStmt->bindParam(':token', $token);
                $updateStmt->bindParam(':expires', $expires);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // In a real app, you would send an email here with the reset link
                $message = 'If an account exists with this email, you will receive a password reset link shortly.';
            } else {
                // For security, don't reveal if email exists
                $message = 'If an account exists with this email, you will receive a password reset link shortly.';
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
    <title>AgriVision Pro | Forgot Password</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary:rgb(16, 104, 205);
            --primary-dark:rgb(15, 80, 155);
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
        <!-- Password Reset Card -->
        <div class="bg-white rounded-xl card-shadow overflow-hidden">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-400 to-blue-700 p-6 text-center text-white">
                <div class="flex justify-center mb-3">
                    <i class="fas fa-key text-4xl"></i>
                </div>
                <h1 class="text-2xl font-bold">Reset Password</h1>
                <p class="text-sm opacity-90 mt-1">Enter your email to receive a reset link</p>
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
                
                <?php if (!empty($message)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        <span class="text-sm text-green-700"><?= htmlspecialchars($message) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400"></i>
                            </div>
                            <input type="email" name="email" required
                                class="pl-10 input-focus block w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none"
                                placeholder="Enter your email">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary w-full text-white py-2 px-4 rounded-lg font-medium flex items-center justify-center">
                        <i class="fas fa-paper-plane mr-2"></i> Send Reset Link
                    </button>
                </form>
            </div>
            
            <!-- Footer Section -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="text-center text-sm text-gray-600">
                    Remember your password? 
                    <a href="index.php" class="font-medium text-blue-700 hover:text-blue-400">Sign in</a>
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