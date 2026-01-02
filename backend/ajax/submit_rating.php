<?php
require_once '../config.php';

if (!is_logged_in() || $_SESSION['role'] !== 'passenger') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ride_id = intval($_POST['ride_id']);
    $rating = intval($_POST['rating']);
    $user_id = $_SESSION['user_id'];

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
        exit();
    }

    // Update ride rating
    $stmt = $conn->prepare("UPDATE rides SET rating = ? WHERE ride_id = ? AND passenger_id = ? AND ride_status = 'completed'");
    $stmt->bind_param("iii", $rating, $ride_id, $user_id);
    
    if ($stmt->execute()) {
        // Also update driver's average rating
        $driver_id_query = "SELECT driver_id FROM rides WHERE ride_id = $ride_id";
        $driver_id = $conn->query($driver_id_query)->fetch_assoc()['driver_id'];

        if ($driver_id) {
            $avg_query = "SELECT AVG(rating) as new_avg FROM rides WHERE driver_id = $driver_id AND rating IS NOT NULL";
            $new_avg = $conn->query($avg_query)->fetch_assoc()['new_avg'];
            
            $update_driver = $conn->prepare("UPDATE driver_details SET rating = ? WHERE driver_id = ?");
            $update_driver->bind_param("di", $new_avg, $driver_id);
            $update_driver->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Thank you for your feedback!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
