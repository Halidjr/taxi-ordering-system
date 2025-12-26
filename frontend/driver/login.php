<?php
require_once '../backend/config.php';

// If already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    switch ($_SESSION['role']) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'driver':
            redirect('driver/dashboard.php');
            break;
        case 'passenger':
            redirect('passenger/dashboard.php');
            break;
    }
}

$error = '';
$success = '';

if (isset($_GET['error'])) {
    $error = $_GET['error'] === 'unauthorized' ? 'Unauthorized access!' : 'Login failed!';
}

if (isset($_GET['logout'])) {
    $success = 'Logged out successfully!';
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password, full_name, role, status FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // For demo purposes, using simple password verification
            // In production, use password_verify() with hashed passwords
            // Verify password (supports both demo accounts and real hashed passwords)
            if (password_verify($password, $user['password'])) {
                
                if ($user['status'] !== 'active') {
                    if ($user['status'] === 'pending_approval') {
                        $error = 'Your account is pending admin approval.';
                    } else {
                        $error = 'Your account is ' . $user['status'];
                    }
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Log the action
                    log_action($user['user_id'], 'login', 'User logged in successfully');
                    
                    // Redirect based on role
                    switch ($user['role']) {
                        case 'admin':
                            redirect('admin/dashboard.php');
                            break;
                        case 'driver':
                            redirect('driver/dashboard.php');
                            break;
                        case 'passenger':
                            redirect('passenger/dashboard.php');
                            break;
                    }
                }
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🚕 <?php echo SITE_NAME; ?></h1>
                <p>Welcome back! Please login to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
                
                <div style="margin-top: 15px; text-align: center;">
                    Don't have an account? <a href="register.php" style="color: var(--primary-color);">Register here</a>
                </div>
            </form>
            

        </div>
    </div>
</body>
</html>
