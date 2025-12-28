
// US-09: Driver can accept assigned ride
<?php
require_once '../../backend/config.php';
check_role('passenger');

$user_id = $_SESSION['user_id'];

// Get passenger statistics
$total_rides_query = "SELECT COUNT(*) as total FROM rides WHERE passenger_id = ?";
$stmt = $conn->prepare($total_rides_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_rides = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$completed_rides_query = "SELECT COUNT(*) as total FROM rides WHERE passenger_id = ? AND ride_status = 'completed'";
$stmt = $conn->prepare($completed_rides_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_rides = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$active_rides_query = "SELECT COUNT(*) as total FROM rides WHERE passenger_id = ? AND ride_status IN ('requested', 'assigned', 'in_progress')";
$stmt = $conn->prepare($active_rides_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_rides = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get recent rides
$recent_rides_query = "SELECT r.*, u.full_name as driver_name, u.phone as driver_phone 
                       FROM rides r 
                       LEFT JOIN users u ON r.driver_id = u.user_id 
                       WHERE r.passenger_id = ? 
                       ORDER BY r.request_time DESC 
                       LIMIT 5";
$stmt = $conn->prepare($recent_rides_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_rides = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get notifications
$notifications = get_unread_notifications($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passenger Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🚕 Taxi System</h2>
                <p>Passenger Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="request_ride.php">🚖 Request Ride</a></li>
                <li><a href="my_rides.php">📋 My Rides</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo $_SESSION['full_name']; ?>!</h1>
                <div class="user-info">
                    <div class="notification-icon" onclick="showModal('notificationModal')">
                        🔔
                        <?php if (count($notifications) > 0): ?>
                            <span class="notification-badge"><?php echo count($notifications); ?></span>
                        <?php endif; ?>
                    </div>
                    <span><?php echo $_SESSION['username']; ?></span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Rides</h3>
                        <p><?php echo $total_rides; ?></p>
                    </div>
                    <div class="stat-icon">📊</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Completed Rides</h3>
                        <p><?php echo $completed_rides; ?></p>
                    </div>
                    <div class="stat-icon">✅</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Active Rides</h3>
                        <p><?php echo $active_rides; ?></p>
                    </div>
                    <div class="stat-icon">🚖</div>
                </div>
            </div>

            <!-- Quick Action -->
            <div class="card">
                <div class="card-header">Quick Action</div>
                <div class="card-body">
                    <a href="request_ride.php" class="btn btn-primary" style="text-align: center;">
                        🚖 Request a New Ride
                    </a>
                </div>
            </div>

            <!-- Recent Rides -->
            <div class="card">
                <div class="card-header">Recent Rides</div>
                <div class="card-body">
                    <?php if (count($recent_rides) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ride ID</th>
                                        <th>Pickup</th>
                                        <th>Dropoff</th>
                                        <th>Driver</th>
                                        <th>Status</th>
                                        <th>Request Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_rides as $ride): ?>
                                        <tr>
                                            <td>#<?php echo $ride['ride_id']; ?></td>
                                            <td><?php echo htmlspecialchars($ride['pickup_location']); ?></td>
                                            <td><?php echo htmlspecialchars($ride['dropoff_location']); ?></td>
                                            <td><?php echo $ride['driver_name'] ?? 'Not Assigned'; ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch ($ride['ride_status']) {
                                                    case 'completed':
                                                        $status_class = 'badge-success';
                                                        break;
                                                    case 'in_progress':
                                                        $status_class = 'badge-warning';
                                                        break;
                                                    case 'cancelled':
                                                        $status_class = 'badge-danger';
                                                        break;
                                                    default:
                                                        $status_class = 'badge-info';
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $ride['ride_status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($ride['request_time'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🚖</div>
                            <h3>No rides yet</h3>
                            <p>Request your first ride to get started!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Notification Modal -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Notifications</h2>
                <span class="close" onclick="closeModal('notificationModal')">&times;</span>
            </div>
            <div class="modal-body">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="alert alert-<?php echo $notification['notification_type'] === 'system' ? 'warning' : 'success'; ?>">
                            <strong><?php echo ucfirst(str_replace('_', ' ', $notification['notification_type'])); ?></strong><br>
                            <?php echo htmlspecialchars($notification['message']); ?><br>
                            <small><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d;">No new notifications</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
