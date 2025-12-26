<!-- US-01: Passenger registration -->
<?php
require_once '../backend/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $dob_input = $_POST['date_of_birth'];

    // Validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email) || empty($phone) || empty($dob_input)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check existing user
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username or Email already exists';
        } else {
            // Age Calculation
            $dob = new DateTime($dob_input);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
            
            if ($age < 16) {
                $error = 'You must be at least 16 years old to register.';
            } else {
                $status = 'active';
                //$role = 'passenger'; // Removed hardcoded role
                
                $status = 'active';
                $success = 'Registration successful! You can now login.';

                // Create User
                // Note: Password hashing should be used in production, but using plain/simple hash as per existing code style (or update to password_hash if possible, but keeping consistent with login.php which seems to check specific strings? No, login.php uses $2y$10... hashes in the INSERTs in database.sql but the login logic checks plaintext for demo? 
                // Wait, login.php checks: if ($password === 'admin123' ...). 
                // BUT database.sql inserts hashes. 
                // Let's check login.php again. 
                // Ah, login.php has: 
                // if ($password === 'admin123' && $user['role'] === 'admin' ...)
                // It essentially ignores the DB password hash for the demo accounts? 
                // But for new users, it should probably use the hash.
                // Actually, looking at login.php lines 48-50, it is HARDCODED for the demo users.
                // But it ALSO fetches the user from DB. If I register a new user, they won't match those hardcoded checks.
                // I need to update login.php to support normal password verification for non-demo users.
                
                // For now, I will use password_hash() here.
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $role = $_POST['role'] === 'driver' ? 'driver' : 'passenger';
                
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, status, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssss", $username, $hashed_password, $full_name, $email, $phone, $role, $status, $dob_input);
                
                if ($stmt->execute()) {
                    $new_user_id = $conn->insert_id;
                    
                    if ($role === 'driver') {
                        $vehicle_number = sanitize_input($_POST['vehicle_number']);
                        $vehicle_type = sanitize_input($_POST['vehicle_type']);
                        $license_number = sanitize_input($_POST['license_number']);
                        $vehicle_capacity = intval($_POST['vehicle_capacity']);
                        
                        $stmt_driver = $conn->prepare("INSERT INTO driver_details (driver_id, vehicle_number, vehicle_type, license_number, vehicle_capacity, availability) VALUES (?, ?, ?, ?, ?, 'available')");
                        $stmt_driver->bind_param("isssi", $new_user_id, $vehicle_number, $vehicle_type, $license_number, $vehicle_capacity);
                        $stmt_driver->execute();
                    }
                    log_action($new_user_id, 'register', "New user registered. Status: $status");

                    if ($status === 'active') {
                        // Redirect to login after short delay or just show success
                    }
                } else {
                    $error = 'Registration failed: ' . $conn->error;
                }
            }
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
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container" style="max-width: 500px;">
        <div class="login-box">
            <div class="login-header">
                <h1>🚕 <?php echo SITE_NAME; ?></h1>
                <p>Create a new account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <p><a href="login.php">Click here to Login</a></p>
                </div>
            <?php else: ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="role">Account Type</label>
                    <select id="role" name="role" required onchange="toggleDriverFields()">
                        <option value="passenger">Passenger</option>
                        <option value="driver">Driver</option>
                    </select>
                </div>

                <div id="driver-fields" style="display: none;">
                    <div class="form-group">
                        <label for="vehicle_number">Vehicle Number (Plate)</label>
                        <input type="text" id="vehicle_number" name="vehicle_number">
                    </div>
                    <div class="form-group">
                        <label for="vehicle_type">Vehicle Type</label>
                        <input type="text" id="vehicle_type" name="vehicle_type" placeholder="e.g. Dolphin, Abadula">
                    </div>
                    <div class="form-group">
                        <label for="vehicle_capacity">Vehicle Capacity</label>
                        <input type="number" id="vehicle_capacity" name="vehicle_capacity" min="1" max="50" value="5">
                    </div>
                    <div class="form-group">
                        <label for="license_number">License Number</label>
                        <input type="text" id="license_number" name="license_number">
                    </div>
                </div>

                <script>
                function toggleDriverFields() {
                    const role = document.getElementById('role').value;
                    const driverFields = document.getElementById('driver-fields');
                    const inputs = driverFields.querySelectorAll('input');
                    
                    if (role === 'driver') {
                        driverFields.style.display = 'block';
                        inputs.forEach(input => input.required = true);
                    } else {
                        driverFields.style.display = 'none';
                        inputs.forEach(input => input.required = false);
                    }
                }
                </script>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
                
                <div style="margin-top: 15px; text-align: center;">
                    Already have an account? <a href="login.php" style="color: var(--primary-color);">Login here</a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
