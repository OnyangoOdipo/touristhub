<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a tourist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tourist') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$booking_id = filter_input(INPUT_POST, 'booking_id', FILTER_SANITIZE_NUMBER_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_SANITIZE_NUMBER_INT);
$comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);

if (!$booking_id || !$rating || !$comment) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Verify the booking belongs to this tourist and is completed
$stmt = $conn->prepare("
    SELECT guide_id FROM bookings 
    WHERE booking_id = ? AND tourist_id = ? AND status = 'completed'
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid booking']);
    exit;
}

try {
    $conn->beginTransaction();

    // Insert review
    $stmt = $conn->prepare("
        INSERT INTO reviews (booking_id, tourist_id, guide_id, rating, comment)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $booking_id,
        $_SESSION['user_id'],
        $booking['guide_id'],
        $rating,
        $comment
    ]);

    // Update guide's average rating
    $stmt = $conn->prepare("
        UPDATE users u
        SET rating = (
            SELECT AVG(r.rating)
            FROM reviews r
            WHERE r.guide_id = u.user_id
        )
        WHERE user_id = ?
    ");
    $stmt->execute([$booking['guide_id']]);

    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollBack();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to submit review']);
} 