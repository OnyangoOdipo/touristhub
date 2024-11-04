<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a guide
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = "Missing required parameters";
    header('Location: bookings.php');
    exit;
}

$booking_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$status = filter_var($_GET['status'], FILTER_SANITIZE_STRING);

// Validate status
$allowed_statuses = ['confirmed', 'cancelled', 'completed'];
if (!in_array($status, $allowed_statuses)) {
    $_SESSION['error'] = "Invalid status";
    header('Location: bookings.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Verify the booking belongs to this guide
$stmt = $conn->prepare("
    SELECT * FROM bookings 
    WHERE booking_id = ? AND guide_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['error'] = "Invalid booking";
    header('Location: bookings.php');
    exit;
}

try {
    $conn->beginTransaction();

    // Update booking status
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET status = ?
        WHERE booking_id = ?
    ");
    $stmt->execute([$status, $booking_id]);

    // If status is completed, create a notification for the tourist
    if ($status === 'completed') {
        // Add notification logic here if you have a notifications table
    }

    $conn->commit();
    $_SESSION['success'] = "Booking status updated successfully";
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Failed to update booking status";
}

header('Location: bookings.php');
exit;
?> 