<?php
require_once '../../backend/config.php';
check_role('admin');

$success = '';
$error = '';

// Handle user status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'activate' || $action === 'suspend' || $action === 'approve') {
        $new_status = ($action === 'activate' || $action === 'approve') ? 'active' : 'suspended';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_status, $user_id);
        
        if ($stmt->execute()) {
            log_action($_SESSION['user_id'], 'update_user_status', "Changed user #$user_id status to $new_status");
            $success = "User status updated successfully!";
        }
        $stmt->close();
    }
}

// Get all users
$users_query = "SELECT u.*, 
                CASE WHEN d.driver_id IS NOT NULL THEN 'Yes' ELSE 'No' END as has_driver_profile
                FROM users u 
                LEFT JOIN driver_details d ON u.user_id = d.driver_id 
                ORDER BY u.created_at DESC";
$users = $conn->query($users_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🚕 Taxi System</h2>
                <p>Admin Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="users.php" class="active">👥 Users</a></li>
                <li><a href="rides.php">🚖 Rides</a></li>
                <li><a href="drivers.php">🚗 Drivers</a></li>
                <li><a href="reports.php">📈 Reports</a></li>
                <li><a href="system_logs.php">📋 System Logs</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>User Management</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">All Users</div>
                <div class="card-body">
                    <div style="margin-bottom: 20px;">
                        <input type="text" id="searchInput" placeholder="Search users..." 
                               onkeyup="filterTable('searchInput', 'usersTable')" 
                               style="padding: 10px; width: 300px; border: 2px solid #ddd; border-radius: 8px;">
                    </div>
                    
                    <div class="table-container">
                        <table id="usersTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Age</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td>
                                            <?php 
                                            if (!empty($user['date_of_birth'])) {
                                                $dob = new DateTime($user['date_of_birth']);
                                                echo $dob->diff(new DateTime())->y;
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($user['status']) {
                                                case 'active':
                                                    $status_class = 'badge-success';
                                                    break;
                                                case 'suspended':
                                                    $status_class = 'badge-danger';
                                                    break;
                                                default:
                                                    $status_class = 'badge-warning';
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($user['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <a href="?action=suspend&id=<?php echo $user['user_id']; ?>" 
                                                       class="btn btn-sm btn-warning"
                                                       onclick="return confirmAction('Suspend this user?')">
                                                        Suspend
                                                    </a>
                                                <?php else: ?>
                                                    <?php if ($user['status'] === 'pending_approval'): ?>
                                                        <a href="?action=approve&id=<?php echo $user['user_id']; ?>" 
                                                           class="btn btn-sm btn-success"
                                                           onclick="return confirmAction('Approve this 16-year-old user?')">
                                                            Approve
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=activate&id=<?php echo $user['user_id']; ?>" 
                                                           class="btn btn-sm btn-success"
                                                           onclick="return confirmAction('Activate this user?')">
                                                            Activate
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
