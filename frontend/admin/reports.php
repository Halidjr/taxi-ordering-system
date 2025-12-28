<?php
require_once '../config.php';
check_role('admin');

// Get various statistics for reports
$total_revenue_query = "SELECT SUM(fare) as total FROM rides WHERE ride_status = 'completed'";
$total_revenue = $conn->query($total_revenue_query)->fetch_assoc()['total'] ?? 0;

$today_rides_query = "SELECT COUNT(*) as total FROM rides WHERE DATE(request_time) = CURDATE()";
$today_rides = $conn->query($today_rides_query)->fetch_assoc()['total'];

$this_month_rides_query = "SELECT COUNT(*) as total FROM rides WHERE MONTH(request_time) = MONTH(CURDATE()) AND YEAR(request_time) = YEAR(CURDATE())";
$this_month_rides = $conn->query($this_month_rides_query)->fetch_assoc()['total'];

$cancelled_rides_query = "SELECT COUNT(*) as total FROM rides WHERE ride_status = 'cancelled'";
$cancelled_rides = $conn->query($cancelled_rides_query)->fetch_assoc()['total'];

// Top drivers
$top_drivers_query = "SELECT u.full_name, d.total_rides, d.rating 
                      FROM users u 
                      JOIN driver_details d ON u.user_id = d.driver_id 
                      ORDER BY d.total_rides DESC 
                      LIMIT 5";
$top_drivers = $conn->query($top_drivers_query)->fetch_all(MYSQLI_ASSOC);

// Rides by status
$status_query = "SELECT ride_status, COUNT(*) as count FROM rides GROUP BY ride_status";
$status_data = $conn->query($status_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🚕 Taxi System</h2>
                <p>Admin Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="users.php">👥 Users</a></li>
                <li><a href="rides.php">🚖 Rides</a></li>
                <li><a href="drivers.php">🚗 Drivers</a></li>
                <li><a href="../frontend/admin/reports.php">📈 Reports</a></li>
                <li><a href="system_logs.php">📋 System Logs</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>System Reports</h1>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Total Revenue</h3>
                        <p>$<?php echo number_format($total_revenue, 2); ?></p>
                    </div>
                    <div class="stat-icon">💰</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Rides Today</h3>
                        <p><?php echo $today_rides; ?></p>
                    </div>
                    <div class="stat-icon">📅</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Rides This Month</h3>
                        <p><?php echo $this_month_rides; ?></p>
                    </div>
                    <div class="stat-icon">📊</div>
                </div>

                <div class="stat-card">
                    <div class="stat-info">
                        <h3>Cancelled Rides</h3>
                        <p><?php echo $cancelled_rides; ?></p>
                    </div>
                    <div class="stat-icon">❌</div>
                </div>
            </div>

            <!-- Rides by Status -->
            <div class="card">
                <div class="card-header">Rides by Status</div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = array_sum(array_column($status_data, 'count'));
                                foreach ($status_data as $status): 
                                    $percentage = ($status['count'] / $total) * 100;
                                ?>
                                    <tr>
                                        <td><strong><?php echo ucfirst(str_replace('_', ' ', $status['ride_status'])); ?></strong></td>
                                        <td><?php echo $status['count']; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div style="width: 200px; background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">
                                                    <div style="width: <?php echo $percentage; ?>%; background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); height: 100%;"></div>
                                                </div>
                                                <span><?php echo number_format($percentage, 1); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Drivers -->
            <div class="card">
                <div class="card-header">Top Performing Drivers</div>
                <div class="card-body">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Driver Name</th>
                                    <th>Total Rides</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ($top_drivers as $driver): 
                                ?>
                                    <tr>
                                        <td><strong><?php echo $rank++; ?></strong></td>
                                        <td><?php echo htmlspecialchars($driver['full_name']); ?></td>
                                        <td><?php echo $driver['total_rides']; ?></td>
                                        <td><?php echo number_format($driver['rating'], 2); ?> ⭐</td>
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
