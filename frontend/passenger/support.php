<?php
require_once '../../backend/config.php';
check_role('passenger');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize_input($_POST['subject']);
    $priority = sanitize_input($_POST['priority']);
    $message = sanitize_input($_POST['message']);

    if (empty($subject) || empty($message)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, subject, priority, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $subject, $priority, $message);
        
        if ($stmt->execute()) {
            $success = "Your support ticket has been created! Our team will review it shortly.";
            log_action($user_id, 'created_ticket', "Subject: $subject");
        } else {
            $error = "Failed to create ticket. Please try again.";
        }
    }
}

// Get existing tickets
$tickets_query = "SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($tickets_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Portal - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <li><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                <li><a href="request_ride.php"><i class="fas fa-plus-circle"></i> Request Ride</a></li>
                <li><a href="support.php" class="active"><i class="fas fa-headset"></i> Support</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="header">
                <h1><i class="fas fa-headset"></i> Support Portal</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="card">
                    <div class="card-header"><i class="fas fa-plus-circle"></i> Create New Ticket</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label>Subject</label>
                                <input type="text" name="subject" required placeholder="Brief summary of the issue">
                            </div>
                            <div class="form-group">
                                <label>Priority</label>
                                <select name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Message</label>
                                <textarea name="message" rows="5" required placeholder="Describe your issue in detail..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Submit Ticket</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><i class="fas fa-history"></i> Ticket History</div>
                    <div class="card-body">
                        <?php if (count($tickets) > 0): ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Subject</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $t): ?>
                                            <tr>
                                                <td>#<?php echo $t['ticket_id']; ?></td>
                                                <td><?php echo htmlspecialchars($t['subject']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = [
                                                        'open' => 'badge-info',
                                                        'pending' => 'badge-warning',
                                                        'resolved' => 'badge-success',
                                                        'closed' => 'badge-danger'
                                                    ];
                                                    ?>
                                                    <span class="badge <?php echo $status_class[$t['status']]; ?>">
                                                        <?php echo ucfirst($t['status']); ?>
                                                    </span>
                                                </td>
                                                <td><small><?php echo date('M d, Y', strtotime($t['created_at'])); ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">You haven't submitted any tickets yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>
