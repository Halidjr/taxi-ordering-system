<?php
require_once '../config.php';
require_once '../auto_assign_trips.php';

check_role('admin');

header('Content-Type: application/json');

try {
    // Capture output buffering to return clean JSON
    ob_start();
    $result = assignPassengersToTrips();
    $output = ob_get_clean();
    
    // Log the manual trigger
    log_action($_SESSION['user_id'], 'manual_trip_assignment', "Admin triggered auto-assignment. Result: $result");
    
    echo json_encode([
        'success' => true, 
        'message' => "Assignment Logic Executed.",
        'details' => $result
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
