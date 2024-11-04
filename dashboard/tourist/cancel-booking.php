<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a tourist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tourist') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No booking specified";
    header('Location: bookings.php');
    exit;
}

$booking_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

$db = new Database();
$conn = $db->getConnection();

// Verify the booking belongs to this tourist and is cancellable
$stmt = $conn->prepare("
    SELECT * FROM bookings 
    WHERE booking_id = ? AND tourist_id = ? AND status = 'pending'
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    $_SESSION['error'] = "Invalid booking or booking cannot be cancelled";
    header('Location: bookings.php');
    exit;
}

// Cancel the booking
$stmt = $conn->prepare("
    UPDATE bookings 
    SET status = 'cancelled' 
    WHERE booking_id = ?
");

if ($stmt->execute([$booking_id])) {
    $_SESSION['success'] = "Booking cancelled successfully";
} else {
    $_SESSION['error'] = "Failed to cancel booking";
}

header('Location: bookings.php');
exit; 