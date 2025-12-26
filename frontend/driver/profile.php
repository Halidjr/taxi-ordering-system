<?php
require_once '../../backend/config.php';
check_role('driver');

$user_id = $_SESSION['user_id'];

// Get user and driver information
$stmt = $conn->prepare("SELECT u.*, d.* FROM users u 
                        LEFT JOIN driver_details d ON u.user_id = d.driver_id 
                        WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🚕 Taxi System</h2>
                <p>Driver Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="my_rides.php">📋 My Rides</a></li>
                <li><a href="ride_history.php">📜 Ride History</a></li>
                <li><a href="profile.php" class="active">👤 Profile</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>My Profile</h1>
            </div>

            <div class="card">
                <div class="card-header">Personal Information</div>
                <div class="card-body">
                    <div style="line-height: 2.5;">
                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                        <p><strong>Role:</strong> <span class="badge badge-info"><?php echo ucfirst($user['role']); ?></span></p>
                        <p><strong>Status:</strong> <span class="badge badge-success"><?php echo ucfirst($user['status']); ?></span></p>
                        <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Driver Information</div>
                <div class="card-body">
                    <div style="line-height: 2.5;">
                        <p><strong>Vehicle Number:</strong> <?php echo htmlspecialchars($user['vehicle_number']); ?></p>
                        <p><strong>Vehicle Type:</strong> <?php echo htmlspecialchars($user['vehicle_type']); ?></p>
                        <p><strong>License Number:</strong> <?php echo htmlspecialchars($user['license_number']); ?></p>
                        <p><strong>Total Rides:</strong> <?php echo $user['total_rides']; ?></p>
                        <p><strong>Rating:</strong> <?php echo number_format($user['rating'], 2); ?> ⭐</p>
                        <p><strong>Current Status:</strong> 
                            <?php
                            $status_class = '';
                            switch ($user['availability']) {
                                case 'available':
                                    $status_class = 'badge-success';
                                    break;
                                case 'busy':
                                    $status_class = 'badge-warning';
                                    break;
                                default:
                                    $status_class = 'badge-danger';
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo strtoupper($user['availability']); ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
