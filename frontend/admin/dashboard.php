<?php
require_once '../../backend/config.php';
check_role('admin');

// Get system statistics
$total_users_query = "SELECT COUNT(*) as total FROM users";
$total_users = $conn->query($total_users_query)->fetch_assoc()['total'];

$total_drivers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'driver'";
$total_drivers = $conn->query($total_drivers_query)->fetch_assoc()['total'];

$total_passengers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'passenger'";
$total_passengers = $conn->query($total_passengers_query)->fetch_assoc()['total'];

$total_rides_query = "SELECT COUNT(*) as total FROM rides";
$total_rides = $conn->query($total_rides_query)->fetch_assoc()['total'];

$active_rides_query = "SELECT COUNT(*) as total FROM rides WHERE ride_status IN ('requested', 'assigned', 'in_progress')";
$active_rides = $conn->query($active_rides_query)->fetch_assoc()['total'];

$completed_rides_query = "SELECT COUNT(*) as total FROM rides WHERE ride_status = 'completed'";
$completed_rides = $conn->query($completed_rides_query)->fetch_assoc()['total'];

$available_drivers_query = "SELECT COUNT(*) as total FROM driver_details WHERE availability = 'available'";
$available_drivers = $conn->query($available_drivers_query)->fetch_assoc()['total'];

// Get recent rides
$recent_rides_query = "SELECT r.*, p.full_name as passenger_name, d.full_name as driver_name 
                       FROM rides r 
                       JOIN users p ON r.passenger_id = p.user_id 
                       LEFT JOIN users d ON r.driver_id = d.user_id 
                       ORDER BY r.request_time DESC 
                       LIMIT 10";
$recent_rides = $conn->query($recent_rides_query)->fetch_all(MYSQLI_ASSOC);

// Get recent system logs
$recent_logs_query = "SELECT l.*, u.username 
                      FROM system_logs l 
                      LEFT JOIN users u ON l.user_id = u.user_id 
                      ORDER BY l.created_at DESC 
                      LIMIT 10";
$recent_logs = $conn->query($recent_logs_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="rides.php">🚖 Rides</a></li>
                <li><a href="taxi_trips.php">🚐 Taxi Trips</a></li>
                <li><a href="drivers.php">🚗 Drivers</a></li>
                <li><a href="reports.php">📈 Reports</a></li>
                <li><a href="system_logs.php">📋 System Logs</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span>👤 <?php echo $_SESSION['full_name']; ?></span>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <p><?php echo $total_users; ?></p>
                    </div>
                    <div class="stat-icon">👥</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Drivers</h3>
                        <p><?php echo $total_drivers; ?></p>
                    </div>
                    <div class="stat-icon">🚗</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Passengers</h3>
                        <p><?php echo $total_passengers; ?></p>
                    </div>
                    <div class="stat-icon">👤</div>
                </div>

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
                        <h3>Completed Rides</h3>
                        <p><?php echo $completed_rides; ?></p>
                    </div>
                    <div class="stat-icon">✅</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Available Drivers</h3>
                        <p><?php echo $available_drivers; ?></p>
                    </div>
                    <div class="stat-icon">🟢</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>System Status</h3>
                        <p style="color: #27ae60;">Online</p>
                    </div>
                    <div class="stat-icon">✔️</div>
                </div>
            </div>

            <!-- Recent Rides -->
            <div class="card">
                <div class="card-header">Recent Rides</div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Ride ID</th>
                                    <th>Passenger</th>
                                    <th>Driver</th>
                                    <th>Pickup</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_rides as $ride): ?>
                                    <tr>
                                        <td><strong>#<?php echo $ride['ride_id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($ride['passenger_name']); ?></td>
                                        <td><?php echo $ride['driver_name'] ?? 'Not Assigned'; ?></td>
                                        <td><?php echo htmlspecialchars(substr($ride['pickup_location'], 0, 30)) . '...'; ?></td>
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
                                        <td><?php echo date('M d, H:i', strtotime($ride['request_time'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent System Logs -->
            <div class="card">
                <div class="card-header">Recent System Activity</div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['username'] ?? 'System'; ?></td>
                                        <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($log['description'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                        <td><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></td>
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
