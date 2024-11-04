<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$recipient_id = filter_input(INPUT_POST, 'recipient_id', FILTER_SANITIZE_NUMBER_INT);
$is_typing = filter_input(INPUT_POST, 'is_typing', FILTER_VALIDATE_BOOLEAN);

$db = new Database();
$conn = $db->getConnection();

try {
    // Update or insert typing status
    $stmt = $conn->prepare("
        INSERT INTO typing_status (user_id, recipient_id, is_typing)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE is_typing = ?, last_updated = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$_SESSION['user_id'], $recipient_id, $is_typing, $is_typing]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to update typing status']);
} 