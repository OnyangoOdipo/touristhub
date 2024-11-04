<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a hub admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Hub') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get date range filters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get overall statistics
$stats = [
    'total_users' => 0,
    'total_guides' => 0,
    'total_tourists' => 0,
    'total_bookings' => 0,
    'completed_tours' => 0,
    'total_revenue' => 0,
    'average_rating' => 0
];

// Get user statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN role = 'Guide' THEN 1 END) as total_guides,
        COUNT(CASE WHEN role = 'Tourist' THEN 1 END) as total_tourists
    FROM users
    WHERE role != 'Hub'
");
$stmt->execute();
$user_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats = array_merge($stats, $user_stats);

// Get booking statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tours,
        COALESCE(SUM(amount), 0) as total_revenue
    FROM bookings
    WHERE booking_date BETWEEN ? AND ?
");
$stmt->execute([$start_date, $end_date]);
$booking_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats = array_merge($stats, $booking_stats);

// Get average guide rating
$stmt = $conn->prepare("
    SELECT AVG(rating) as average_rating 
    FROM users 
    WHERE role = 'Guide' AND rating > 0
");
$stmt->execute();
$rating_stats = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['average_rating'] = $rating_stats['average_rating'] ?? 0;

// Get top performing guides
$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.name,
        u.rating,
        COUNT(DISTINCT b.booking_id) as total_bookings,
        COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_tours,
        COALESCE(SUM(b.amount), 0) as total_revenue
    FROM users u
    LEFT JOIN bookings b ON u.user_id = b.guide_id
    WHERE u.role = 'Guide'
    GROUP BY u.user_id
    ORDER BY total_revenue DESC
    LIMIT 5
");
$stmt->execute();
$top_guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular destinations
$stmt = $conn->prepare("
    SELECT 
        d.*,
        u.name as guide_name,
        COUNT(b.booking_id) as booking_count,
        COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_count
    FROM destinations d
    JOIN users u ON d.guide_id = u.user_id
    LEFT JOIN bookings b ON d.destination_id = b.destination_id
    GROUP BY d.destination_id
    ORDER BY booking_count DESC
    LIMIT 5
");
$stmt->execute();
$popular_destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity
$stmt = $conn->prepare("
    (SELECT 
        'booking' as type,
        b.created_at as timestamp,
        CONCAT(u.name, ' booked ', d.name) as description,
        b.status
    FROM bookings b
    JOIN users u ON b.tourist_id = u.user_id
    JOIN destinations d ON b.destination_id = d.destination_id
    ORDER BY b.created_at DESC
    LIMIT 5)
    UNION ALL
    (SELECT 
        'review' as type,
        r.created_at as timestamp,
        CONCAT(u.name, ' reviewed ', g.name) as description,
        CAST(r.rating as CHAR) as status
    FROM reviews r
    JOIN users u ON r.tourist_id = u.user_id
    JOIN users g ON r.guide_id = g.user_id
    ORDER BY r.created_at DESC
    LIMIT 5)
    ORDER BY timestamp DESC
    LIMIT 10
");
$stmt->execute();
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-lg font-medium text-gray-900">Platform Analytics</h2>
                <p class="mt-1 text-sm text-gray-500">Comprehensive overview of platform performance</p>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="mt-8 bg-white shadow rounded-lg p-6">
            <form method="GET" class="flex space-x-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                    <input type="date" name="start_date" value="<?= $start_date ?>"
                           class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                    <input type="date" name="end_date" value="<?= $end_date ?>"
                           class="mt-1 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Update
                    </button>
                </div>
            </form>
        </div>

        <!-- Key Metrics -->
        <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= number_format($stats['total_users']) ?></dd>
                                <dd class="text-xs text-gray-500">
                                    <?= number_format($stats['total_guides']) ?> Guides • 
                                    <?= number_format($stats['total_tourists']) ?> Tourists
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Bookings -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Bookings</dt>
                                <dd class="text-lg font-semibold text-gray-900"><?= number_format($stats['total_bookings']) ?></dd>
                                <dd class="text-xs text-gray-500">
                                    <?= number_format($stats['completed_tours']) ?> Completed Tours
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                <dd class="text-lg font-semibold text-gray-900">$<?= number_format($stats['total_revenue'], 2) ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Rating -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Average Guide Rating</dt>
                                <dd class="text-lg font-semibold text-gray-900">
                                    <?= number_format($stats['average_rating'], 1) ?> / 5.0
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Guides and Popular Destinations -->
        <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
            <!-- Top Performing Guides -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg font-medium text-gray-900">Top Performing Guides</h3>
                </div>
                <div class="border-t border-gray-200">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($top_guides as $guide): ?>
                            <li class="px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($guide['name']) ?></p>
                                        <div class="flex items-center mt-1">
                                            <span class="text-yellow-400 text-sm">
                                                <?= str_repeat('★', round($guide['rating'])) ?>
                                            </span>
                                            <span class="ml-2 text-sm text-gray-500">
                                                <?= $guide['completed_tours'] ?> tours completed
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">
                                            $<?= number_format($guide['total_revenue'], 2) ?>
                                        </p>
                                        <p class="text-xs text-gray-500">Revenue</p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Popular Destinations -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg font-medium text-gray-900">Popular Destinations</h3>
                </div>
                <div class="border-t border-gray-200">
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($popular_destinations as $destination): ?>
                            <li class="px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($destination['name']) ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?= htmlspecialchars($destination['location']) ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Guide: <?= htmlspecialchars($destination['guide_name']) ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= $destination['booking_count'] ?> bookings
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            <?= $destination['completed_count'] ?> completed
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
            </div>
            <div class="border-t border-gray-200">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($recent_activity as $activity): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <?php if ($activity['type'] === 'booking'): ?>
                                        <span class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </span>
                                    <?php else: ?>
                                        <span class="flex-shrink-0 h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                            <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                            </svg>
                                        </span>
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <p class="text-sm text-gray-900"><?= htmlspecialchars($activity['description']) ?></p>
                                        <p class="text-xs text-gray-500"><?= date('M d, Y H:i', strtotime($activity['timestamp'])) ?></p>
                                    </div>
                                </div>
                                <?php if ($activity['status']): ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full
                                        <?php
                                        if ($activity['type'] === 'booking') {
                                            switch ($activity['status']) {
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
                                        } else {
                                            echo 'bg-yellow-100 text-yellow-800';
                                        }
                                        ?>">
                                        <?php
                                        if ($activity['type'] === 'review') {
                                            echo $activity['status'] . ' ★';
                                        } else {
                                            echo ucfirst($activity['status']);
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?> 