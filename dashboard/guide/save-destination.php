<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a guide
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Get form data
$destination_id = filter_input(INPUT_POST, 'destination_id', FILTER_SANITIZE_NUMBER_INT);
$name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
$region = filter_input(INPUT_POST, 'region', FILTER_SANITIZE_STRING);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

// Handle activities array
$activities = isset($_POST['activities']) ? $_POST['activities'] : [];
$activities_string = !empty($activities) ? implode(',', $activities) : '';

// Validate required fields
if (!$name || !$location || !$region || !$description) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    // Handle image upload
    $image_name = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            throw new Exception('Invalid image type');
        }

        if ($_FILES['image']['size'] > $max_size) {
            throw new Exception('Image too large');
        }

        $upload_dir = '../../assets/images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $image_name = uniqid() . '_' . $_FILES['image']['name'];
        $upload_path = $upload_dir . $image_name;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            throw new Exception('Failed to upload image');
        }
    }

    if ($destination_id) {
        // Update existing destination
        $stmt = $conn->prepare("
            UPDATE destinations 
            SET name = ?, 
                location = ?, 
                region = ?, 
                description = ?,
                activities = ?
                " . ($image_name ? ", image = ?" : "") . "
            WHERE destination_id = ? AND guide_id = ?
        ");

        $params = [$name, $location, $region, $description, $activities_string];
        if ($image_name) {
            $params[] = $image_name;
        }
        $params[] = $destination_id;
        $params[] = $_SESSION['user_id'];

        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Destination not found or not authorized');
        }
    } else {
        // Create new destination
        $stmt = $conn->prepare("
            INSERT INTO destinations (guide_id, name, location, region, description, activities, image)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $name,
            $location,
            $region,
            $description,
            $activities_string,
            $image_name
        ]);
    }

    $conn->commit();
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    // Delete uploaded image if exists
    if (isset($image_name) && file_exists($upload_dir . $image_name)) {
        unlink($upload_dir . $image_name);
    }
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
} 