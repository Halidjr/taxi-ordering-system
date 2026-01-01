<?php
require_once '../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ride_id = isset($_POST['ride_id']) ? intval($_POST['ride_id']) : 0;
    $new_status = isset($_POST['status']) ? sanitize_input($_POST['status']) : '';
    
    if (!$ride_id || !$new_status) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Fetch current ride details
        $stmt = $conn->prepare("SELECT * FROM rides WHERE ride_id = ? FOR UPDATE");
        $stmt->bind_param("i", $ride_id);
        $stmt->execute();
        $ride = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$ride) {
            throw new Exception("Ride not found");
        }

        // Authorization check
        if ($role === 'driver' && $ride['driver_id'] != $user_id) {
            throw new Exception("Permission denied");
        }
        
        // Handle transitions
        $update_fields = ["ride_status = ?"];
        $params = [$new_status];
        $types = "s";

        $notification_message = "";
        $notification_type = "";
        $recipient_id = $ride['passenger_id'];

        switch ($new_status) {
            case 'assigned':
                $notification_message = "Your ride has been assigned to a driver.";
                $notification_type = 'ride_assigned';
                break;
            case 'in_progress':
                $update_fields[] = "start_time = NOW()";
                $notification_message = "Your ride has started.";
                $notification_type = 'ride_started';
                break;
            case 'completed':
                $fare = rand(15, 60) + (rand(0, 99) / 100); // Simulated fare calculation
                $update_fields[] = "end_time = NOW()";
                $update_fields[] = "fare = ?";
                $params[] = $fare;
                $types .= "d";
                $notification_message = "Your ride is complete. Fare: $" . number_format($fare, 2);
                $notification_type = 'ride_completed';
                
                // Update driver availability
                $update_driver = $conn->prepare("UPDATE driver_details SET availability = 'available', total_rides = total_rides + 1 WHERE driver_id = ?");
                $update_driver->bind_param("i", $ride['driver_id']);
                $update_driver->execute();
                $update_driver->close();
                break;
            case 'cancelled':
                $notification_message = "Your ride has been cancelled.";
                $notification_type = 'system';
                
                // If cancelled, make driver available again
                if ($ride['driver_id']) {
                    $update_driver = $conn->prepare("UPDATE driver_details SET availability = 'available' WHERE driver_id = ?");
                    $update_driver->bind_param("i", $ride['driver_id']);
                    $update_driver->execute();
                    $update_driver->close();
                }
                break;
        }

        // Apply update
        $sql = "UPDATE rides SET " . implode(", ", $update_fields) . " WHERE ride_id = ?";
        $params[] = $ride_id;
        $types .= "i";
        
        $update_stmt = $conn->prepare($sql);
        $update_stmt->bind_param($types, ...$params);
        $update_stmt->execute();
        $update_stmt->close();

        // Trigger notification
        if ($notification_message) {
            create_notification($recipient_id, $notification_message, $notification_type);
        }

        // Log action
        log_action($user_id, 'update_ride_status', "Updated ride #$ride_id status to $new_status");

        $conn->commit();
        echo json_encode(['success' => true, 'message' => "Ride status updated to $new_status"]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
