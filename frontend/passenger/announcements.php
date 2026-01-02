<?php
require_once '../../backend/config.php';
check_role('passenger');

$user_id = $_SESSION['user_id'];

// Get announcements
$announcements_query = "SELECT a.*, u.full_name as author_name 
                        FROM announcements a 
                        LEFT JOIN users u ON a.author_id = u.user_id 
                        ORDER BY a.created_at DESC";
$announcements = $conn->query($announcements_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .announcement-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary-color);
            transition: transform 0.3s;
        }
        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .announcement-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .announcement-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 215, 0, 0.1);
            color: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .announcement-meta {
            font-size: 0.85rem;
            color: var(--light-text);
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-taxi"></i> Taxi System</h2>
                <p>Passenger Portal</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="announcements.php" class="active"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="request_ride.php"><i class="fas fa-plus-circle"></i> Request Ride</a></li>
                <li><a href="my_rides.php"><i class="fas fa-list"></i> My Rides</a></li>
                <li><a href="support.php"><i class="fas fa-headset"></i> Support</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><i class="fas fa-bullhorn"></i> System Announcements</h1>
            </div>

            <?php if (count($announcements) > 0): ?>
                <?php foreach ($announcements as $ann): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <div class="announcement-icon">
                                <i class="fas fa-<?php echo htmlspecialchars($ann['icon']); ?>"></i>
                            </div>
                            <div>
                                <h2 style="color: var(--secondary-color);"><?php echo htmlspecialchars($ann['title']); ?></h2>
                                <small class="text-muted"><?php echo date('F j, Y', strtotime($ann['created_at'])); ?></small>
                            </div>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                        </div>
                        <div class="announcement-meta">
                            <span><i class="fas fa-user-edit"></i> Published by: <?php echo htmlspecialchars($ann['author_name']); ?></span>
                            <span><i class="fas fa-check-circle"></i> Official Update</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-bullhorn"></i></div>
                    <h3>No announcements yet</h3>
                    <p>Stay tuned for system updates and community news!</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
