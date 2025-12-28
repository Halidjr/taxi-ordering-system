<?php
require_once '../../config.php';
check_role('passenger');

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_location = sanitize_input($_POST['pickup_location']);
    $dropoff_location = sanitize_input($_POST['dropoff_location']);
    $num_passengers = intval($_POST['num_passengers'] ?? 1);
    $passenger_notes = sanitize_input($_POST['passenger_notes'] ?? '');
    
    if (empty($pickup_location) || empty($dropoff_location)) {
        $error = 'Please fill in all required fields';
    } else {
        // Insert ride request
        $stmt = $conn->prepare("INSERT INTO rides (passenger_id, pickup_location, dropoff_location, passenger_notes, ride_status) VALUES (?, ?, ?, ?, 'requested')");
        $stmt->bind_param("isss", $user_id, $pickup_location, $dropoff_location, $passenger_notes);
        
        if ($stmt->execute()) {
            $ride_id = $stmt->insert_id;
            $stmt->close();
            
            // Log action
            log_action($user_id, 'request_ride', "Requested ride #$ride_id for $num_passengers passengers");
            
            // Automated Assignment Logic
            // Find available driver with enough capacity (assuming 4 as default but can be extended)
            $driver_query = "SELECT d.driver_id, d.vehicle_type 
                            FROM driver_details d 
                            JOIN users u ON d.driver_id = u.user_id 
                            WHERE d.availability = 'available' 
                            AND u.status = 'active'
                            ORDER BY RAND() LIMIT 1";
            $driver_result = $conn->query($driver_query);
            
            if ($driver_result->num_rows > 0) {
                $driver = $driver_result->fetch_assoc();
                $driver_id = $driver['driver_id'];
                
                // Assign driver to ride
                $update_stmt = $conn->prepare("UPDATE rides SET driver_id = ?, ride_status = 'assigned' WHERE ride_id = ?");
                $update_stmt->bind_param("ii", $driver_id, $ride_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Update driver availability
                $driver_update = $conn->prepare("UPDATE driver_details SET availability = 'busy' WHERE driver_id = ?");
                $driver_update->bind_param("i", $driver_id);
                $driver_update->execute();
                $driver_update->close();
                
                create_notification($user_id, "Your ride #$ride_id has been assigned to a driver!", 'ride_assigned');
                create_notification($driver_id, "New ride #$ride_id assigned to you!", 'ride_request');
                
                $success = "Ride requested successfully! A driver is on their way.";
            } else {
                create_notification($user_id, "Your ride #$ride_id is pending. Searching for nearby drivers...", 'ride_request');
                $success = "Ride requested! Searching for available drivers nearby...";
            }
        } else {
            $error = "Failed to process ride request. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Ride | Taxi System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
    <div class="container">
        <div class="glass-card animate-up">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1>🚖 Request a Ride</h1>
                <a href="../../passenger/dashboard.php" class="btn btn-secondary">Dashboard</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span>❌</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span>✅</span> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="pickup_location">Pickup Location</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📍</span>
                        <input type="text" id="pickup_location" name="pickup_location" required 
                               placeholder="Enter pickup address...">
                    </div>
                </div>

                <div class="form-group">
                    <label for="dropoff_location">Destination</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🏁</span>
                        <input type="text" id="dropoff_location" name="dropoff_location" required 
                               placeholder="Where are you going?">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="num_passengers">Passengers</label>
                        <div class="input-wrapper">
                            <span class="input-icon">👥</span>
                            <input type="number" id="num_passengers" name="num_passengers" value="1" min="1" max="16">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ride_type">Ride Type</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🚗</span>
                            <select id="ride_type" name="ride_type">
                                <option value="standard">Standard Sedan</option>
                                <option value="premium">Premium SUV</option>
                                <option value="van">Luxury Van</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="passenger_notes">Notes for Driver (Optional)</label>
                    <textarea id="passenger_notes" name="passenger_notes" rows="3" 
                              placeholder="Any special instructions?"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    🚀 Request Ride Now
                </button>
            </form>
        </div>

        <div class="stats-grid animate-up" style="animation-delay: 0.2s;">
            <div class="glass-card stat-item">
                <div class="stat-value">5m</div>
                <div class="stat-label">Avg. Pickup</div>
            </div>
            <div class="glass-card stat-item">
                <div class="stat-value">4.9</div>
                <div class="stat-label">Driver Rating</div>
            </div>
            <div class="glass-card stat-item">
                <div class="stat-value">$12</div>
                <div class="stat-label">Est. Min Fare</div>
            </div>
        </div>
    </div>
</body>
</html>
