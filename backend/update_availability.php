<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!is_logged_in() || $_SESSION['role'] !== 'driver') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $user_id = $_SESSION['user_id'];
    $status = sanitize_input($_POST['status']);
    
    // Validate status
    $valid_statuses = ['available', 'busy', 'offline'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Update driver availability
    $stmt = $conn->prepare("UPDATE driver_details SET availability = ? WHERE driver_id = ?");
    $stmt->bind_param("si", $status, $user_id);
    
    if ($stmt->execute()) {
        log_action($user_id, 'update_availability', "Changed availability to $status");
        echo json_encode(['success' => true, 'message' => 'Availability updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update availability']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
