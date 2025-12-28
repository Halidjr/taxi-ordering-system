<?php
require_once '../config.php';

// Check if user is logged in and is admin
// check_role() function is defined in config.php or functions.php included in config.php
// Assuming check_role checks session or similar. However, since this is an AJAX request, 
// a redirect might not be appropriate if it fails. Ideally it returns 403.
// But following existing patterns, if check_role redirects, it might return HTML login page.
// Let's assume for now we use the session check directly or assume check_role works.
// Better to check session manually for AJAX to return JSON error.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // Query active rides
    $query = "SELECT r.ride_id, r.pickup_location, r.dropoff_location, r.ride_status, r.request_time,
              p.full_name as passenger_name, d.full_name as driver_name
              FROM rides r 
              JOIN users p ON r.passenger_id = p.user_id 
              LEFT JOIN users d ON r.driver_id = d.user_id 
              WHERE r.ride_status IN ('requested', 'assigned', 'in_progress')
              ORDER BY r.request_time DESC";
              
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception($conn->error);
    }
    
    $rides = [];
    while ($row = $result->fetch_assoc()) {
        $rides[] = $row;
    }
    
    echo json_encode(['status' => 'success', 'data' => $rides]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
