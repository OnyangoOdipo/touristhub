<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$receiver_id = filter_input(INPUT_POST, 'receiver_id', FILTER_SANITIZE_NUMBER_INT);
$message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
$file_attachment = null;
$file_type = null;

// Handle file upload
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($_FILES['file']['type'], $allowed_types)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid file type']);
        exit;
    }

    if ($_FILES['file']['size'] > $max_size) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'File too large']);
        exit;
    }

    $upload_dir = '../../uploads/attachments/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = uniqid() . '_' . $_FILES['file']['name'];
    $file_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
        $file_attachment = $file_name;
        $file_type = $_FILES['file']['type'];
    }
}

if (!$receiver_id || (!$message && !$file_attachment)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, file_attachment, file_type, status)
        VALUES (?, ?, ?, ?, ?, 'sent')
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $receiver_id,
        $message,
        $file_attachment,
        $file_type
    ]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to send message']);
} 