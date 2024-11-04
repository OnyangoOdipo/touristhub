<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a guide
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Check if required parameters are provided
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['error'] = "Missing required parameters";
    header('Location: bookings.php');
    exit;
}

$booking_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$action = filter_var($_GET['action'], FILTER_SANITIZE_STRING);

// Map actions to status values that match the database enum exactly
$status_map = [
    'accept' => 'confirmed',    // lowercase to match database
    'decline' => 'cancelled',   // lowercase to match database
    'complete' => 'completed'   // lowercase to match database
];

// Validate action
if (!array_key_exists($action, $status_map)) {
    $_SESSION['error'] = "Invalid action";
    header('Location: bookings.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    // Start transaction
    $conn->beginTransaction();

    // Verify the booking belongs to this guide and is in a valid state
    $stmt = $conn->prepare("
        SELECT b.*, t.email as tourist_email, d.name as destination_name 
        FROM bookings b
        JOIN users t ON b.tourist_id = t.user_id
        JOIN destinations d ON b.destination_id = d.destination_id
        WHERE b.booking_id = ? AND b.guide_id = ?
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Invalid booking");
    }

    // Debug information
    error_log("Current booking status: " . $booking['status']);
    error_log("Attempting to change to: " . $status_map[$action]);

    // Determine new status based on action and validate current status
    $new_status = $status_map[$action];
    
    // Validate status transitions (using lowercase for comparison)
    if ($action === 'accept' && strtolower($booking['status']) !== 'pending') {
        throw new Exception("Booking can only be accepted when pending");
    }
    if ($action === 'decline' && strtolower($booking['status']) !== 'pending') {
        throw new Exception("Booking can only be declined when pending");
    }
    if ($action === 'complete' && strtolower($booking['status']) !== 'confirmed') {
        throw new Exception("Only confirmed bookings can be marked as completed");
    }

    // Update booking status
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = ?
        WHERE booking_id = ?
    ");
    
    $result = $stmt->execute([$new_status, $booking_id]);
    
    if (!$result) {
        error_log("Failed to update booking status. Error: " . print_r($stmt->errorInfo(), true));
        throw new Exception("Failed to update booking status");
    }

    // Create notification message
    $message = '';
    switch ($action) {
        case 'accept':
            $message = "Your booking for {$booking['destination_name']} has been confirmed by the guide.";
            break;
        case 'decline':
            $message = "Your booking for {$booking['destination_name']} has been cancelled by the guide.";
            break;
        case 'complete':
            $message = "Your tour of {$booking['destination_name']} has been marked as completed. Please leave a review!";
            break;
    }

    // Insert notification/message to tourist
    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, status)
        VALUES (?, ?, ?, 'sent')
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $booking['tourist_id'],
        $message
    ]);

    // Commit transaction
    $conn->commit();

    $_SESSION['success'] = "Booking has been " . $status_map[$action] . " successfully";

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    error_log("Error in booking-action.php: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header('Location: bookings.php');
exit;
?> 