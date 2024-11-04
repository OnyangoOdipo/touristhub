<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

if (!isset($_GET['id'])) {
    echo "Destination ID not provided";
    exit;
}

$destination_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

$db = new Database();
$conn = $db->getConnection();

// Get destination details with guide information
$stmt = $conn->prepare("
    SELECT d.*, 
           u.name as guide_name,
           u.rating as guide_rating,
           u.contact_info as guide_contact,
           COUNT(DISTINCT b.booking_id) as total_bookings,
           GROUP_CONCAT(DISTINCT d.activities) as activity_list
    FROM destinations d
    LEFT JOIN users u ON d.guide_id = u.user_id
    LEFT JOIN bookings b ON d.destination_id = b.destination_id
    WHERE d.destination_id = ?
    GROUP BY d.destination_id
");

$stmt->execute([$destination_id]);
$destination = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$destination) {
    echo "Destination not found";
    exit;
}

// Get reviews for this destination
$stmt = $conn->prepare("
    SELECT r.*, u.name as tourist_name
    FROM reviews r
    JOIN users u ON r.tourist_id = u.user_id
    JOIN bookings b ON r.booking_id = b.booking_id
    WHERE b.destination_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->execute([$destination_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert activities string to array
$activities = $destination['activity_list'] ? explode(',', $destination['activity_list']) : [];
?>

<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-start">
        <div>
            <h2 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($destination['name']) ?></h2>
            <p class="text-gray-600"><?= htmlspecialchars($destination['location']) ?></p>
        </div>
        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Image -->
    <div class="mt-4 relative h-64 rounded-lg overflow-hidden">
        <img src="<?= BASE_URL ?>/assets/images/<?= $destination['image'] ?? 'default.jpg' ?>"
            alt="<?= htmlspecialchars($destination['name']) ?>"
            class="w-full h-full object-cover">
    </div>

    <!-- Description -->
    <div class="mt-4">
        <h3 class="text-lg font-medium text-gray-900">About this destination</h3>
        <p class="mt-2 text-gray-600">
            <?= nl2br(htmlspecialchars($destination['description'])) ?>
        </p>
    </div>

    <!-- Activities -->
    <?php if (!empty($activities)): ?>
        <div class="mt-6">
            <h3 class="text-lg font-medium text-gray-900">Available Activities</h3>
            <div class="mt-2 flex flex-wrap gap-2">
                <?php foreach ($activities as $activity): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        <?= htmlspecialchars(trim($activity)) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Guide Information -->
    <?php if ($destination['guide_name']): ?>
        <div class="mt-6">
            <h3 class="text-lg font-medium text-gray-900">Your Guide</h3>
            <div class="mt-2 flex items-center">
                <div class="flex-shrink-0 h-12 w-12 bg-gray-200 rounded-full"></div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($destination['guide_name']) ?></p>
                    <div class="flex items-center">
                        <span class="text-yellow-400"><?= str_repeat('★', $destination['guide_rating']) ?></span>
                        <span class="ml-2 text-sm text-gray-500"><?= $destination['total_bookings'] ?> tours completed</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Reviews -->
    <?php if (!empty($reviews)): ?>
        <div class="mt-6">
            <h3 class="text-lg font-medium text-gray-900">Recent Reviews</h3>
            <div class="mt-2 space-y-4">
                <?php foreach ($reviews as $review): ?>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($review['tourist_name']) ?></p>
                            <span class="text-yellow-400"><?= str_repeat('★', $review['rating']) ?></span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600"><?= htmlspecialchars($review['comment']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Action Buttons -->
<div class="mt-6 flex justify-end space-x-4">
    <button onclick="closeModal()" 
            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
        Close
    </button>
    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Tourist'): ?>
        <button onclick="bookDestination(<?= $destination['destination_id'] ?>)"
                class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
            Book Now
        </button>
    <?php endif; ?>
</div>
</div>

<script>
    // Add this function to your get-destination.php file
    function bookDestination(destinationId) {
        window.location.href = `book.php?destination_id=${destinationId}`;
    }
</script>