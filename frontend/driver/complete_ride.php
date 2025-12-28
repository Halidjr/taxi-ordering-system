<?php
require_once '../config.php';
check_role('driver');

$user_id = $_SESSION['user_id'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $ride_id = intval($_GET['id']);
    
    // Verify ride belongs to this driver and is in progress
    $verify_query = $conn->prepare("SELECT * FROM rides WHERE ride_id = ? AND driver_id = ? AND ride_status = 'in_progress'");
    $verify_query->bind_param("ii", $ride_id, $user_id);
    $verify_query->execute();
    $result = $verify_query->get_result();
    
    if ($result->num_rows > 0) {
        $ride = $result->fetch_assoc();
        
        // Calculate random fare (in real app, would calculate based on distance)
        $fare = rand(10, 50) + (rand(0, 99) / 100);
        
        // Update ride status to completed
        $update_stmt = $conn->prepare("UPDATE rides SET ride_status = 'completed', end_time = NOW(), fare = ? WHERE ride_id = ?");
        $update_stmt->bind_param("di", $fare, $ride_id);
        
        if ($update_stmt->execute()) {
            // Update driver statistics
            $update_driver = $conn->prepare("UPDATE driver_details SET availability = 'available', total_rides = total_rides + 1 WHERE driver_id = ?");
            $update_driver->bind_param("i", $user_id);
            $update_driver->execute();
            $update_driver->close();
            
            // Create notification for passenger
            create_notification($ride['passenger_id'], "Your ride #$ride_id has been completed! Fare: $" . number_format($fare, 2), 'ride_completed');
            
            // Log action
            log_action($user_id, 'complete_ride', "Completed ride #$ride_id");
            
            redirect('dashboard.php');
        }
        $update_stmt->close();
    }
    $verify_query->close();
}

redirect('dashboard.php');
?>
