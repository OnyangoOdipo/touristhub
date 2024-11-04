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
    header('Location: verify-guides.php');
    exit;
}

$guide_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$action = filter_var($_GET['action'], FILTER_SANITIZE_STRING);

if (!in_array($action, ['verify', 'reject'])) {
    $_SESSION['error'] = "Invalid action";
    header('Location: verify-guides.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Get guide details for notification
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'Guide'");
    $stmt->execute([$guide_id]);
    $guide = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$guide) {
        throw new Exception("Guide not found");
    }

    // Update guide status
    $new_status = $action === 'verify' ? 'verified' : 'rejected';
    $stmt = $conn->prepare("
        UPDATE users 
        SET status = ? 
        WHERE user_id = ? AND role = 'Guide'
    ");
    $stmt->execute([$new_status, $guide_id]);

    // Send notification message to guide
    $message = $action === 'verify' 
        ? "Your guide account has been verified. You can now start accepting bookings."
        : "Your guide account verification was not successful. Please contact support for more information.";

    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, status)
        VALUES (?, ?, ?, 'sent')
    ");
    $stmt->execute([$_SESSION['user_id'], $guide_id, $message]);

    $conn->commit();
    $_SESSION['success'] = "Guide has been " . ($action === 'verify' ? 'verified' : 'rejected') . " successfully";

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: verify-guides.php');
exit;
?> 