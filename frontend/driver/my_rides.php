<?php
require_once '../../backend/config.php';
check_role('driver');

$user_id = $_SESSION['user_id'];

// Get active rides
$active_rides_query = "SELECT r.*, u.full_name as passenger_name, u.phone as passenger_phone 
                       FROM rides r 
                       JOIN users u ON r.passenger_id = u.user_id 
                       WHERE r.driver_id = ? AND r.ride_status IN ('assigned', 'in_progress') 
                       ORDER BY r.request_time DESC";
$stmt = $conn->prepare($active_rides_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$active_rides = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Rides - <?php echo SITE_NAME; ?></title>
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
                <li><a href="my_rides.php" class="active">📋 My Rides</a></li>
                <li><a href="ride_history.php">📜 Ride History</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>My Active Rides</h1>
            </div>

            <div class="card">
                <div class="card-header">Active Rides</div>
                <div class="card-body">
                    <?php if (count($active_rides) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ride ID</th>
                                        <th>Passenger</th>
                                        <th>Pickup</th>
                                        <th>Dropoff</th>
                                        <th>Status</th>
                                        <th>Request Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_rides as $ride): ?>
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
                                            <td><?php echo date('M d, Y H:i', strtotime($ride['request_time'])); ?></td>
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

    <script src="../assets/js/main.js"></script>
</body>
</html>
