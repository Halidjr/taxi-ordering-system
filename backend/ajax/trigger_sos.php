<?php
require_once '../config.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// For simplicity, we trigger a system-wide notification for all admins
$admin_query = "SELECT user_id FROM users WHERE role = 'admin'";
$result = $conn->query($admin_query);

if ($result) {
    while ($admin = $result->fetch_assoc()) {
        $message = "EMERGENCY: SOS triggered by $full_name ($role). Immediate attention required!";
        create_notification($admin['user_id'], $message, 'emergency');
    }
    
    log_action($user_id, 'SOS_TRIGGERED', "User triggered emergency alert");
    
    echo json_encode(['success' => true, 'message' => 'Emergency alerts sent to administrators.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
