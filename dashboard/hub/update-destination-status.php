<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a hub admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Hub') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['error'] = "Missing required parameters";
    header('Location: destinations.php');
    exit;
}

$destination_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$action = filter_var($_GET['action'], FILTER_SANITIZE_STRING);

if (!in_array($action, ['activate', 'deactivate'])) {
    $_SESSION['error'] = "Invalid action";
    header('Location: destinations.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $status = $action === 'activate' ? 'active' : 'inactive';
    
    $stmt = $conn->prepare("
        UPDATE destinations 
        SET status = ? 
        WHERE destination_id = ?
    ");
    
    $stmt->execute([$status, $destination_id]);
    
    $_SESSION['success'] = "Destination " . ($status === 'active' ? 'activated' : 'deactivated') . " successfully";
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to update destination status";
}

header('Location: destinations.php');
exit;
?> 