<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a hub admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Hub') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

$db = new Database();
$conn = $db->getConnection();

// Get user details with stats
$stmt = $conn->prepare("
    SELECT 
        u.*,
        p.bio,
        p.languages,
        COUNT(DISTINCT b.booking_id) as total_bookings,
        COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_bookings,
        COUNT(DISTINCT CASE WHEN b.status = 'cancelled' THEN b.booking_id END) as cancelled_bookings,
        COALESCE(AVG(r.rating), 0) as average_rating,
        COUNT(DISTINCT r.review_id) as total_reviews
    FROM users u
    LEFT JOIN profiles p ON u.user_id = p.user_id
    LEFT JOIN bookings b ON (u.role = 'Tourist' AND u.user_id = b.tourist_id) 
                        OR (u.role = 'Guide' AND u.user_id = b.guide_id)
    LEFT JOIN reviews r ON u.user_id = r.guide_id
    WHERE u.user_id = ?
    GROUP BY u.user_id
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php');
    exit;
}

// Get recent activity
$stmt = $conn->prepare("
    SELECT 
        'booking' as type,
        b.booking_id as id,
        b.status,
        b.created_at as timestamp,
        d.name as destination_name,
        CASE 
            WHEN u.user_id = b.tourist_id THEN 'Booked a tour'
            ELSE 'Received a booking'
        END as action
    FROM bookings b
    JOIN destinations d ON b.destination_id = d.destination_id
    JOIN users u ON b.tourist_id = u.user_id
    WHERE b.tourist_id = ? OR b.guide_id = ?
    UNION ALL
    SELECT 
        'review' as type,
        r.review_id as id,
        NULL as status,
        r.created_at as timestamp,
        d.name as destination_name,
        'Left a review' as action
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN destinations d ON b.destination_id = d.destination_id
    WHERE r.tourist_id = ? OR r.guide_id = ?
    ORDER BY timestamp DESC
    LIMIT 10
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get reviews if user is a guide
if ($user['role'] === 'Guide') {
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
    $stmt->execute([$user_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="users.php" class="flex items-center text-gray-600 hover:text-gray-900">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Users
            </a>
        </div>

        <!-- User Profile Header -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">
                            <?= htmlspecialchars($user['name']) ?>
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-500">
                            <?= $user['email'] ?> • <?= ucfirst($user['role']) ?>
                        </p>
                    </div>
                    <span class="px-2 py-1 text-sm font-medium rounded-full
                        <?php
                        switch($user['status']) {
                            case 'verified':
                                echo 'bg-green-100 text-green-800';
                                break;
                            case 'pending':
                                echo 'bg-yellow-100 text-yellow-800';
                                break;
                            case 'rejected':
                                echo 'bg-red-100 text-red-800';
                                break;
                        }
                        ?>">
                        <?= ucfirst($user['status']) ?>
                    </span>
                </div>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-3">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?= date('F j, Y', strtotime($user['created_at'])) ?>
                        </dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Total Bookings</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= $user['total_bookings'] ?></dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Completed Tours</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= $user['completed_bookings'] ?></dd>
                    </div>
                    <?php if ($user['role'] === 'Guide'): ?>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Rating</dt>
                            <dd class="mt-1 text-sm text-gray-900 flex items-center">
                                <span class="text-yellow-400"><?= str_repeat('★', round($user['average_rating'])) ?></span>
                                <span class="ml-1">(<?= number_format($user['average_rating'], 1) ?>)</span>
                            </dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500">Total Reviews</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= $user['total_reviews'] ?></dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($user['bio']): ?>
                        <div class="sm:col-span-3">
                            <dt class="text-sm font-medium text-gray-500">Bio</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <?= nl2br(htmlspecialchars($user['bio'])) ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                    <?php if ($user['languages']): ?>
                        <div class="sm:col-span-3">
                            <dt class="text-sm font-medium text-gray-500">Languages</dt>
                            <dd class="mt-1">
                                <div class="flex flex-wrap gap-2">
                                    <?php foreach (explode(',', $user['languages']) as $language): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= htmlspecialchars(trim($language)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
            </div>
            <div class="border-t border-gray-200">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($activities as $activity): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($activity['action']) ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        <?= htmlspecialchars($activity['destination_name']) ?>
                                    </p>
                                </div>
                                <div class="flex items-center">
                                    <?php if ($activity['status']): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full
                                            <?php
                                            switch($activity['status']) {
                                                case 'confirmed':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'cancelled':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                case 'completed':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                            }
                                            ?>">
                                            <?= ucfirst($activity['status']) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="ml-4 text-sm text-gray-500">
                                        <?= date('M d, Y', strtotime($activity['timestamp'])) ?>
                                    </span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Reviews Section for Guides -->
        <?php if ($user['role'] === 'Guide' && !empty($reviews)): ?>
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg font-medium text-gray-900">Recent Reviews</h3>
                </div>
                <div class="border-t border-gray-200">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($reviews as $review): ?>
                            <li class="px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($review['tourist_name']) ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?= htmlspecialchars($review['destination_name']) ?>
                                        </p>
                                        <p class="mt-1 text-sm text-gray-600">
                                            <?= htmlspecialchars($review['comment']) ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-yellow-400">
                                            <?= str_repeat('★', $review['rating']) ?>
                                        </span>
                                        <span class="ml-4 text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($review['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?> 