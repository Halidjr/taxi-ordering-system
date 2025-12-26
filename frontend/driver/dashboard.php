<!-- US-04: Driver availability feature -->
<?php
require_once '../../backend/config.php';
check_role('driver');

$user_id = $_SESSION['user_id'];

// Get driver details
$driver_query = "SELECT * FROM driver_details WHERE driver_id = ?";
$stmt = $conn->prepare($driver_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$driver = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get statistics
$total_rides = $driver['total_rides'] ?? 0;

$active_rides_query = "SELECT COUNT(*) as total FROM rides WHERE driver_id = ? AND ride_status IN ('assigned', 'in_progress')";
$stmt = $conn->prepare($active_rides_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_rides = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$completed_today_query = "SELECT COUNT(*) as total FROM rides WHERE driver_id = ? AND ride_status = 'completed' AND DATE(end_time) = CURDATE()";
$stmt = $conn->prepare($completed_today_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$completed_today = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get pending/assigned rides
$pending_rides_query = "SELECT r.*, u.full_name as passenger_name, u.phone as passenger_phone 
                        FROM rides r 
                        JOIN users u ON r.passenger_id = u.user_id 
                        WHERE r.driver_id = ? AND r.ride_status IN ('assigned', 'in_progress') 
                        ORDER BY r.request_time DESC";
$stmt = $conn->prepare($pending_rides_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_rides = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get notifications
$notifications = get_unread_notifications($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🚕 Taxi System</h2>
                <p>Driver Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="my_rides.php">📋 My Rides</a></li>
                <li><a href="ride_history.php">📜 Ride History</a></li>
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

            <!-- Availability Status -->
            <div class="card">
                <div class="card-header">Availability Status</div>
                <div class="card-body">
                    <p><strong>Current Status:</strong> 
                        <?php
                        $status_class = '';
                        switch ($driver['availability']) {
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
                        <span class="badge <?php echo $status_class; ?>" style="font-size: 1.1rem;">
                            <?php echo strtoupper($driver['availability']); ?>
                        </span>
                    </p>
                    <div class="action-buttons" style="margin-top: 15px;">
                        <?php if ($driver['availability'] !== 'available'): ?>
                            <button class="btn btn-success" onclick="toggleAvailability('available')">
                                ✅ Go Online
                            </button>
                        <?php endif; ?>
                        <?php if ($driver['availability'] !== 'offline'): ?>
                            <button class="btn btn-danger" onclick="toggleAvailability('offline')">
                                🔴 Go Offline
                            </button>
                        <?php endif; ?>
                    </div>
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
                        <h3>Active Rides</h3>
                        <p><?php echo $active_rides; ?></p>
                    </div>
                    <div class="stat-icon">🚖</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Completed Today</h3>
                        <p><?php echo $completed_today; ?></p>
                    </div>
                    <div class="stat-icon">✅</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Rating</h3>
                        <p><?php echo number_format($driver['rating'], 2); ?> ⭐</p>
                    </div>
                    <div class="stat-icon">🌟</div>
                </div>
            </div>

            <!-- Active/Pending Rides -->
            <div class="card">
                <div class="card-header">Active Rides</div>
                <div class="card-body">
                    <?php if (count($pending_rides) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ride ID</th>
                                        <th>Passenger</th>
                                        <th>Pickup</th>
                                        <th>Dropoff</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_rides as $ride): ?>
                                        <tr>
                                            <td><strong>#<?php echo $ride['ride_id']; ?></strong></td>
                                            <td>
                                                <?php echo htmlspecialchars($ride['passenger_name']); ?><br>
                                                <small>📞 <?php echo htmlspecialchars($ride['passenger_phone']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($ride['pickup_location']); ?></td>
                                            <td><?php echo htmlspecialchars($ride['dropoff_location']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $ride['ride_status'] === 'in_progress' ? 'badge-warning' : 'badge-info'; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $ride['ride_status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($ride['ride_status'] === 'assigned'): ?>
                                                        <a href="start_ride.php?id=<?php echo $ride['ride_id']; ?>" class="btn btn-sm btn-success">
                                                            Start
                                                        </a>
                                                    <?php elseif ($ride['ride_status'] === 'in_progress'): ?>
                                                        <a href="complete_ride.php?id=<?php echo $ride['ride_id']; ?>" class="btn btn-sm btn-primary">
                                                            Complete
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🚖</div>
                            <h3>No active rides</h3>
                            <p>You don't have any active rides at the moment.</p>
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
