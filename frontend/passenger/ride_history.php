<?php
require_once '../../backend/config.php';
check_role('passenger');

$user_id = $_SESSION['user_id'];

// Get completed rides using helper function
$completed_rides = get_ride_history($user_id, 'passenger');

// Calculate total spent
$total_spent = 0;
foreach ($completed_rides as $ride) {
    $total_spent += floatval($ride['fare'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride History - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🚕 Taxi System</h2>
                <p>Passenger Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="request_ride.php">🚗 Request Ride</a></li>
                <li><a href="my_rides.php">📋 My Rides</a></li>
                <li><a href="ride_history.php" class="active">📜 Ride History</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="../logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1>Ride History</h1>
            </div>

            <div class="card" style="margin-bottom: 25px;">
                <div class="card-body">
                    <div style="text-align: center;">
                        <h2 style="color: var(--secondary-color); margin-bottom: 10px;">Total Spent</h2>
                        <p style="font-size: 2.5rem; color: var(--primary-color); font-weight: bold;">
                            $<?php echo number_format($total_spent, 2); ?>
                        </p>
                        <p style="color: var(--light-text);">On <?php echo count($completed_rides); ?> completed rides</p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Past Rides</div>
                <div class="card-body">
                    <?php if (count($completed_rides) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ride ID</th>
                                        <th>Driver</th>
                                        <th>Pickup</th>
                                        <th>Dropoff</th>
                                        <th>Fare</th>
                                        <th>Date</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_rides as $ride): ?>
                                        <tr>
                                            <td><strong>#<?php echo $ride['ride_id']; ?></strong></td>
                                            <td><?php echo htmlspecialchars($ride['other_party_name'] ?? 'Not Assigned'); ?></td>
                                            <td><?php echo htmlspecialchars(substr($ride['pickup_location'], 0, 30)) . '...'; ?></td>
                                            <td><?php echo htmlspecialchars(substr($ride['dropoff_location'], 0, 30)) . '...'; ?></td>
                                            <td><strong style="color: var(--success-color);">$<?php echo number_format($ride['fare'], 2); ?></strong></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($ride['end_time'])); ?></td>
                                            <td>
                                                <?php
                                                if ($ride['start_time'] && $ride['end_time']) {
                                                    $start = new DateTime($ride['start_time']);
                                                    $end = new DateTime($ride['end_time']);
                                                    $duration = $start->diff($end);
                                                    echo $duration->format('%i min');
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📜</div>
                            <h3>No ride history yet</h3>
                            <p>Your completed rides will appear here.</p>
                            <a href="request_ride.php" class="btn btn-primary" style="margin-top: 15px;">Book your first ride</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
