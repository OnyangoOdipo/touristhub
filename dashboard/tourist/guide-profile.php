<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if guide ID is provided
if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/dashboard/tourist/top-guides.php');
    exit;
}

$guide_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

$db = new Database();
$conn = $db->getConnection();

// Get guide details with stats
$stmt = $conn->prepare("
    SELECT 
        u.*,
        p.bio,
        p.languages,
        COUNT(DISTINCT b.booking_id) as total_tours,
        COUNT(DISTINCT d.destination_id) as total_destinations,
        COUNT(DISTINCT r.review_id) as total_reviews,
        COALESCE(AVG(r.rating), 0) as average_rating,
        GROUP_CONCAT(DISTINCT d.region) as regions
    FROM users u
    LEFT JOIN profiles p ON u.user_id = p.user_id
    LEFT JOIN bookings b ON u.user_id = b.guide_id
    LEFT JOIN destinations d ON u.user_id = d.guide_id
    LEFT JOIN reviews r ON u.user_id = r.guide_id
    WHERE u.user_id = ? AND u.role = 'Guide'
    GROUP BY u.user_id
");
$stmt->execute([$guide_id]);
$guide = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$guide) {
    header('Location: ' . BASE_URL . '/dashboard/tourist/top-guides.php');
    exit;
}

// Get guide's destinations
$stmt = $conn->prepare("
    SELECT * FROM destinations 
    WHERE guide_id = ?
    ORDER BY name ASC
");
$stmt->execute([$guide_id]);
$destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent reviews
$stmt = $conn->prepare("
    SELECT r.*, u.name as tourist_name, d.name as destination_name
    FROM reviews r
    JOIN users u ON r.tourist_id = u.user_id
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN destinations d ON b.destination_id = d.destination_id
    WHERE r.guide_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->execute([$guide_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <!-- Guide Profile Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="flex items-center">
                    <div class="h-20 w-20 rounded-full bg-gray-300 flex items-center justify-center text-2xl text-white">
                        <?= strtoupper(substr($guide['name'], 0, 1)) ?>
                    </div>
                    <div class="ml-4">
                        <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($guide['name']) ?></h1>
                        <div class="flex items-center mt-1">
                            <span class="text-yellow-400">
                                <?= str_repeat('★', round($guide['average_rating'])) ?>
                                <?= str_repeat('☆', 5 - round($guide['average_rating'])) ?>
                            </span>
                            <span class="ml-2 text-sm text-gray-600">
                                (<?= number_format($guide['average_rating'], 1) ?> / 5.0)
                            </span>
                        </div>
                    </div>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <a href="messages.php?guide_id=<?= $guide['user_id'] ?>" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        Contact Guide
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left Column: Guide Info -->
            <div class="lg:col-span-1">
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Guide Information</h2>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-blue-600"><?= $guide['total_tours'] ?></p>
                            <p class="text-sm text-gray-500">Tours Completed</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-bold text-green-600"><?= $guide['total_reviews'] ?></p>
                            <p class="text-sm text-gray-500">Reviews</p>
                        </div>
                    </div>

                    <!-- Bio -->
                    <?php if ($guide['bio']): ?>
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">About</h3>
                            <p class="text-gray-700"><?= nl2br(htmlspecialchars($guide['bio'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Languages -->
                    <?php if ($guide['languages']): ?>
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Languages</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach (explode(',', $guide['languages']) as $language): ?>
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-full">
                                        <?= htmlspecialchars(trim($language)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Regions -->
                    <?php if ($guide['regions']): ?>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Specializes in</h3>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach (array_unique(explode(',', $guide['regions'])) as $region): ?>
                                    <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded-full">
                                        <?= htmlspecialchars(trim($region)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column: Destinations & Reviews -->
            <div class="lg:col-span-2">
                <!-- Destinations -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Available Destinations</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($destinations as $destination): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <h3 class="font-medium text-gray-900"><?= htmlspecialchars($destination['name']) ?></h3>
                                <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($destination['location']) ?></p>
                                <p class="text-sm text-gray-600 line-clamp-2">
                                    <?= htmlspecialchars($destination['description']) ?>
                                </p>
                                <button onclick="viewDestination(<?= $destination['destination_id'] ?>)"
                                        class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                                    View Details →
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Reviews -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Recent Reviews</h2>
                    <?php if (empty($reviews)): ?>
                        <p class="text-gray-500 text-center py-4">No reviews yet</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($reviews as $review): ?>
                                <div class="border-b pb-4 last:border-b-0">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-gray-900">
                                                <?= htmlspecialchars($review['tourist_name']) ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?= htmlspecialchars($review['destination_name']) ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="text-yellow-400">
                                                <?= str_repeat('★', $review['rating']) ?>
                                            </span>
                                            <span class="ml-2 text-sm text-gray-500">
                                                <?= date('M d, Y', strtotime($review['created_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-gray-600"><?= htmlspecialchars($review['comment']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewDestination(destinationId) {
    window.location.href = `destinations.php?id=${destinationId}`;
}
</script>

<?php include '../../includes/footer.php'; ?> 