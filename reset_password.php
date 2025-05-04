<?php
session_start();
require 'includes/db_connect.php';

$error = '';
$success = '';

// Verify token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $stmt = $conn->prepare("SELECT email FROM users WHERE reset_token = :token AND reset_expires > NOW() LIMIT 1");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $email = $user['email'];
            
            // Handle password reset form submission
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'];
                $confirm = $_POST['confirm_password'];
                
                if (empty($password) || empty($confirm)) {
                    $error = 'Both fields are required';
                } elseif ($password !== $confirm) {
                    $error = 'Passwords do not match';
                } elseif (strlen($password) < 8) {
                    $error = 'Password must be at least 8 characters';
                } else {
                    // Update password
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $update = $conn->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_expires = NULL WHERE email = :email");
                    $update->execute([
                        ':password' => $hashed,
                        ':email' => $email
                    ]);
                    
                    $success = 'Password updated successfully. You can now <a href="index.php">login</a>.';
                }
            }
            
            // Show password reset form
            ?>
            <!DOCTYPE html>
            <html>
            <!-- Similar styling to your other pages -->
            <body>
                <form method="POST">
                    <h1>Reset Password</h1>
                    <?php if ($error): ?>
                        <div class="error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <input type="password" name="password" placeholder="New password">
                    <input type="password" name="confirm_password" placeholder="Confirm password">
                    <button type="submit">Reset Password</button>
                </form>
            </body>
            </html>
            <?php
            exit();
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// Invalid or expired token
header("Location: forgot_password.php?error=invalid_token");
exit();