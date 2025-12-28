<?php
require_once '../../config.php';
header('Content-Type: application/json');

// Ensure only admins can access this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$type = $_GET['type'] ?? 'summary';
$response = [];

try {
    switch ($type) {
        case 'revenue':
            // Last 7 days revenue
            $query = "SELECT DATE(end_time) as date, SUM(fare) as total 
                     FROM rides 
                     WHERE ride_status = 'completed' 
                     AND end_time >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                     GROUP BY DATE(end_time) 
                     ORDER BY date ASC";
            $result = $conn->query($query);
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'rides':
            // Rides by status
            $query = "SELECT ride_status, COUNT(*) as count FROM rides GROUP BY ride_status";
            $result = $conn->query($query);
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            break;

        case 'drivers':
            // Top drivers efficiency
            $query = "SELECT u.full_name, d.total_rides, d.rating 
                     FROM driver_details d 
                     JOIN users u ON d.driver_id = u.user_id 
                     ORDER BY d.total_rides DESC LIMIT 10";
            $result = $conn->query($query);
            $response['data'] = $result->fetch_all(MYSQLI_ASSOC);
            break;

        default:
            // Quick summary
            $summary = [
                'total_revenue' => $conn->query("SELECT SUM(fare) FROM rides WHERE ride_status = 'completed'")->fetch_row()[0] ?? 0,
                'total_rides' => $conn->query("SELECT COUNT(*) FROM rides")->fetch_row()[0] ?? 0,
                'active_drivers' => $conn->query("SELECT COUNT(*) FROM driver_details WHERE availability != 'offline'")->fetch_row()[0] ?? 0,
                'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0] ?? 0
            ];
            $response['data'] = $summary;
            break;
    }

    $response['success'] = true;
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
