<?php
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../vendor/autoload.php'; // For payment gateway SDK

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tourist') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);
$amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Verify booking details
    $stmt = $conn->prepare("
        SELECT * FROM bookings 
        WHERE booking_id = ? 
        AND tourist_id = ? 
        AND payment_status = 'pending'
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Invalid booking or payment already processed');
    }

    // Process payment through payment gateway
    // Implementation depends on chosen payment gateway

    // Update booking payment status
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET payment_status = 'paid',
            payment_date = CURRENT_TIMESTAMP
        WHERE booking_id = ?
    ");
    $stmt->execute([$booking_id]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['error' => $e->getMessage()]);
} 