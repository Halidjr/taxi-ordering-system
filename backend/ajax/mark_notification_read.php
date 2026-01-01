<?php
require_once '../config.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    
    if ($notification_id > 0) {
        $user_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE notification_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
