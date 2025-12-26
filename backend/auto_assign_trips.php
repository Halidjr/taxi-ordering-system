<?php
/**
 * Automatic Trip Assignment System
 * This script should be run periodically (e.g., via cron job or when passengers request rides)
 * It groups passengers with similar routes into taxi trips
 */

require_once 'config.php';

// Function to assign passengers to trips
function assignPassengersToTrips() {
    global $conn;
    
    // Get all requested rides (not yet assigned to a trip)
    $pending_rides_query = "SELECT r.*, u.full_name 
                            FROM rides r
                            JOIN users u ON r.passenger_id = u.user_id
                            WHERE r.ride_status = 'requested' 
                            AND r.trip_id IS NULL
                            ORDER BY r.request_time ASC";
    $pending_rides = $conn->query($pending_rides_query)->fetch_all(MYSQLI_ASSOC);
    
    if (empty($pending_rides)) {
        return "No pending rides to process.";
    }
    
    // Group rides by similar pickup/dropoff locations
    $route_groups = [];
    foreach ($pending_rides as $ride) {
        $route_key = md5($ride['pickup_location'] . '|' . $ride['dropoff_location']);
        if (!isset($route_groups[$route_key])) {
            $route_groups[$route_key] = [
                'pickup' => $ride['pickup_location'],
                'dropoff' => $ride['dropoff_location'],
                'rides' => []
            ];
        }
        $route_groups[$route_key]['rides'][] = $ride;
    }
    
    $trips_created = 0;
    $passengers_assigned = 0;
    
    // Process each route group
    foreach ($route_groups as $group) {
        $rides = $group['rides'];
        $pickup = $conn->real_escape_string($group['pickup']);
        $dropoff = $conn->real_escape_string($group['dropoff']);
        
        // Loop through rides in this group
        foreach ($rides as $ride) {
            $trip_id = null;
            $driver_id = null;
            
            // 1. Try to find an existing filling trip for this route with space available
            $existing_trip_query = "SELECT t.trip_id, t.driver_id, t.current_passenger_count, t.max_capacity 
                                   FROM taxi_trips t 
                                   WHERE t.trip_status = 'filling'
                                   AND t.pickup_location = '$pickup'
                                   AND t.dropoff_location = '$dropoff'
                                   AND t.current_passenger_count < t.max_capacity
                                   LIMIT 1";
            $existing_trip = $conn->query($existing_trip_query)->fetch_assoc();
            
            if ($existing_trip) {
                // Use existing trip
                $trip_id = $existing_trip['trip_id'];
                $driver_id = $existing_trip['driver_id'];
                $current_count = $existing_trip['current_passenger_count'];
                $max_capacity = $existing_trip['max_capacity'];
            } else {
                // 2. No filling trip found, find a new available driver
                $driver_query = "SELECT d.driver_id, d.vehicle_capacity 
                                FROM driver_details d
                                JOIN users u ON d.driver_id = u.user_id
                                WHERE d.availability = 'available' 
                                AND u.status = 'active'
                                LIMIT 1";
                $driver_result = $conn->query($driver_query);
                
                if ($driver_result->num_rows > 0) {
                    $driver = $driver_result->fetch_assoc();
                    $driver_id = $driver['driver_id'];
                    $capacity = $driver['vehicle_capacity'] ?? 5;
                    
                    // Create new trip
                    $create_trip = $conn->prepare("INSERT INTO taxi_trips (driver_id, pickup_location, dropoff_location, max_capacity, current_passenger_count) VALUES (?, ?, ?, ?, 0)");
                    $create_trip->bind_param("issi", $driver_id, $group['pickup'], $group['dropoff'], $capacity);
                    $create_trip->execute();
                    $trip_id = $conn->insert_id;
                    $create_trip->close();
                    
                    $current_count = 0;
                    $max_capacity = $capacity;
                    $trips_created++;
                    
                    // Set driver to busy
                    $conn->query("UPDATE driver_details SET availability = 'busy' WHERE driver_id = $driver_id");
                } else {
                    // No driver available at all, skip this ride for now
                    continue; 
                }
            }
            
            // 3. Assign passenger to the trip (whether new or existing)
            $assign_query = $conn->prepare("UPDATE rides SET trip_id = ?, driver_id = ?, ride_status = 'waiting_for_trip' WHERE ride_id = ?");
            $assign_query->bind_param("iii", $trip_id, $driver_id, $ride['ride_id']);
            $assign_query->execute();
            $assign_query->close();
            
            // Notify passenger
            create_notification($ride['passenger_id'], "You have been assigned to Trip #{$trip_id}. Waiting for more passengers...", 'ride_assigned');
            
            $current_count++;
            $passengers_assigned++;
            
            // Update trip count
            $conn->query("UPDATE taxi_trips SET current_passenger_count = $current_count WHERE trip_id = $trip_id");
            
            // Check if full
            if ($current_count >= $max_capacity) {
                $conn->query("UPDATE taxi_trips SET trip_status = 'pending_approval' WHERE trip_id = $trip_id");
                
                // Notify admin
                $admin_query = "SELECT user_id FROM users WHERE role = 'admin' LIMIT 1";
                $admin = $conn->query($admin_query)->fetch_assoc();
                if ($admin) {
                    create_notification($admin['user_id'], "Trip #{$trip_id} is full with {$max_capacity} passengers and requires approval for departure!", 'system');
                }
                
                // Notify driver
                create_notification($driver_id, "Your taxi is full! Trip #{$trip_id} is waiting for admin approval to depart.", 'system');
                
                log_action(null, 'trip_full', "Trip #{$trip_id} reached capacity and is pending admin approval");
            }
        }
    }
    
    return "Processed: $trips_created new trips created, $passengers_assigned passengers assigned.";
}

// Run the assignment if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $result = assignPassengersToTrips();
    echo $result . "\n";
    log_action(null, 'auto_assignment', $result);
}
?>
