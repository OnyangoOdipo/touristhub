<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a guide
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No destination specified";
    header('Location: destinations.php');
    exit;
}

$destination_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // First, check if the destination belongs to this guide and get image filename
    $stmt = $conn->prepare("
        SELECT image 
        FROM destinations 
        WHERE destination_id = ? AND guide_id = ?
    ");
    $stmt->execute([$destination_id, $_SESSION['user_id']]);
    $destination = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$destination) {
        throw new Exception("Destination not found or not authorized");
    }

    // Check if there are any active bookings
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE destination_id = ? 
        AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$destination_id]);
    $active_bookings = $stmt->fetchColumn();

    if ($active_bookings > 0) {
        throw new Exception("Cannot delete destination with active bookings");
    }

    // Delete the destination
    $stmt = $conn->prepare("
        DELETE FROM destinations 
        WHERE destination_id = ? AND guide_id = ?
    ");
    $stmt->execute([$destination_id, $_SESSION['user_id']]);

    // Delete the image file if it exists
    if ($destination['image']) {
        $image_path = '../../assets/images/destinations/' . $destination['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    $conn->commit();
    $_SESSION['success'] = "Destination deleted successfully";

} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: destinations.php');
exit;