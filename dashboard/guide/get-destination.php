<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a guide
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Destination ID not provided']);
    exit;
}

$destination_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

$db = new Database();
$conn = $db->getConnection();

// Get destination details
$stmt = $conn->prepare("
    SELECT * FROM destinations 
    WHERE destination_id = ? AND guide_id = ?
");
$stmt->execute([$destination_id, $_SESSION['user_id']]);
$destination = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$destination) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Destination not found or unauthorized']);
    exit;
}

// Return destination data as JSON
header('Content-Type: application/json');
echo json_encode([
    'destination_id' => $destination['destination_id'],
    'name' => $destination['name'],
    'location' => $destination['location'],
    'region' => $destination['region'],
    'description' => $destination['description'],
    'activities' => $destination['activities'],
    'image' => $destination['image']
]); 