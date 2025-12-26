<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'taxi_ordering_system');

// System Configuration
define('SITE_URL', 'http://localhost/taxi_ordering_system');
define('SITE_NAME', 'Taxi Ordering System');

// Session Configuration
session_start();

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Helper Functions
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function check_role($required_role) {
    if (!is_logged_in()) {
        header("Location: " . SITE_URL . "/frontend/login.php");
        exit();
    }
    
    if ($_SESSION['role'] !== $required_role) {
        header("Location: " . SITE_URL . "/frontend/login.php?error=unauthorized");
        exit();
    }
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function log_action($user_id, $action, $description = null) {
    global $conn;
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $description, $ip_address);
    $stmt->execute();
    $stmt->close();
}

function create_notification($user_id, $message, $type) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, notification_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $message, $type);
    $stmt->execute();
    $stmt->close();
}

function get_unread_notifications($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $notifications;
}
?>
