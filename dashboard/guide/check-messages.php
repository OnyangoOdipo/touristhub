<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$recipient_id = filter_input(INPUT_GET, 'recipient_id', FILTER_SANITIZE_NUMBER_INT);
$last_message_id = filter_input(INPUT_GET, 'last_message_id', FILTER_SANITIZE_NUMBER_INT) ?? 0;

$db = new Database();
$conn = $db->getConnection();

try {
    // Get new messages
    $stmt = $conn->prepare("
        SELECT m.*, u.name as sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE ((sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?))
           AND message_id > ?
        ORDER BY timestamp ASC
    ");
    
    $stmt->execute([
        $_SESSION['user_id'], 
        $recipient_id, 
        $recipient_id, 
        $_SESSION['user_id'],
        $last_message_id
    ]);
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check typing status
    $stmt = $conn->prepare("
        SELECT is_typing 
        FROM typing_status 
        WHERE user_id = ? AND recipient_id = ? 
        AND last_updated >= DATE_SUB(NOW(), INTERVAL 5 SECOND)
    ");
    $stmt->execute([$recipient_id, $_SESSION['user_id']]);
    $typing = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'messages' => $messages,
        'is_typing' => $typing && $typing['is_typing']
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Failed to fetch messages']);
} 