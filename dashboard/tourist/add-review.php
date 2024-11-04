<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is a tourist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tourist') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
$review = filter_input(INPUT_POST, 'review', FILTER_SANITIZE_STRING);

if (!$booking_id || !$rating || !$review) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Validate rating range
if ($rating < 1 || $rating > 5) {
    echo json_encode(['error' => 'Invalid rating value']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // First, check if a review already exists for this booking
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM reviews 
        WHERE booking_id = ?
    ");
    $stmt->execute([$booking_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('A review already exists for this booking');
    }

    // Verify the booking belongs to this tourist and is completed
    $stmt = $conn->prepare("
        SELECT guide_id 
        FROM bookings 
        WHERE booking_id = ? 
        AND tourist_id = ? 
        AND status = 'Completed'
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Invalid booking or booking is not completed');
    }

    // Insert review
    $stmt = $conn->prepare("
        INSERT INTO reviews (
            booking_id, 
            tourist_id, 
            guide_id, 
            rating, 
            comment
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $booking_id,
        $_SESSION['user_id'],
        $booking['guide_id'],
        $rating,
        $review
    ]);

    // Update guide's average rating
    $stmt = $conn->prepare("
        UPDATE users u
        SET rating = (
            SELECT COALESCE(AVG(r.rating), 0)
            FROM reviews r
            WHERE r.guide_id = u.user_id
        )
        WHERE user_id = ?
    ");
    $stmt->execute([$booking['guide_id']]);

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    error_log("Review submission error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} 