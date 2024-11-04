<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a tourist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tourist') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get tourist data
$db = new Database();
$conn = $db->getConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'Tourist'");
$stmt->execute([$_SESSION['user_id']]);
$tourist = $stmt->fetch(PDO::FETCH_ASSOC);

// Get upcoming bookings
$stmt = $conn->prepare("
    SELECT b.*, d.name as destination_name, u.name as guide_name, u.rating as guide_rating
    FROM bookings b 
    JOIN destinations d ON b.destination_id = d.destination_id 
    JOIN users u ON b.guide_id = u.user_id 
    WHERE b.tourist_id = ?
    ORDER BY b.booking_date DESC LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$upcoming_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recommended guides
$stmt = $conn->prepare("
    SELECT u.*, COUNT(b.booking_id) as total_bookings 
    FROM users u 
    LEFT JOIN bookings b ON u.user_id = b.guide_id 
    WHERE u.role = 'Guide' 
    GROUP BY u.user_id 
    ORDER BY u.rating DESC 
    LIMIT 3
");
$stmt->execute();
$recommended_guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function for relative dates
function getRelativeDate($date) {
    $now = new DateTime();
    $bookingDate = new DateTime($date);
    $interval = $now->diff($bookingDate);
    
    if ($interval->days == 0) {
        return "Today";
    } elseif ($interval->days == 1) {
        return "Tomorrow";
    } elseif ($interval->days < 7) {
        return "In " . $interval->days . " days";
    } else {
        return date('M d, Y', strtotime($date));
    }
}

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold"><?php
                $hour = date('H');
                if ($hour < 12) {
                    echo "Good morning, ";
                } elseif ($hour < 17) {
                    echo "Good afternoon, ";
                } else {
                    echo "Good evening, ";
                }
                echo htmlspecialchars($tourist['name']);
            ?></h1>
            <p class="mt-2 text-blue-100">Ready to plan your next adventure?</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Upcoming Tours -->
            <div class="bg-white overflow-hidden shadow-sm hover:shadow-md transition-shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Upcoming Tours</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        <?= count($upcoming_bookings) ?>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="bookings.php" class="font-medium text-blue-600 hover:text-blue-900 flex items-center">
                            View all
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Search Destinations -->
            <div class="bg-white overflow-hidden shadow-sm hover:shadow-md transition-shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Explore Destinations</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    Find your next adventure spot
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="destinations.php" class="font-medium text-green-600 hover:text-green-900 flex items-center">
                            Browse destinations
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Top Rated Guides -->
            <div class="bg-white overflow-hidden shadow-sm hover:shadow-md transition-shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Top Rated Guides</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    View highest rated guides
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="top-guides.php" class="font-medium text-purple-600 hover:text-purple-900 flex items-center">
                            See top guides
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="mt-8">
            <h2 class="text-xl font-bold text-gray-900 pb-3 border-b border-gray-200">Recent Bookings</h2>
            <div class="mt-4 bg-white shadow-sm hover:shadow-md transition-shadow rounded-lg overflow-hidden">
                <?php if (empty($upcoming_bookings)): ?>
                    <div class="p-4 text-gray-500 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2">No upcoming bookings found. Ready to plan your next adventure?</p>
                        <a href="search.php" class="mt-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Find a Guide
                        </a>
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($upcoming_bookings as $booking): ?>
                            <li class="p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-lg font-medium text-blue-600">
                                            <?= htmlspecialchars($booking['destination_name']) ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Guide: <?= htmlspecialchars($booking['guide_name']) ?>
                                            <span class="ml-2 text-yellow-500">
                                                <?= str_repeat('★', $booking['guide_rating']) ?>
                                            </span>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full 
                                            <?php
                                            switch($booking['status']) {
                                                case 'confirmed':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'cancelled':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                            }
                                            ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                        <div class="text-sm text-gray-500">
                                            <?= getRelativeDate($booking['booking_date']) ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recommended Guides -->
        <div class="mt-8">
            <h2 class="text-xl font-bold text-gray-900 pb-3 border-b border-gray-200">Recommended Guides</h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($recommended_guides as $guide): ?>
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-gray-300 rounded-full"></div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900"><?= htmlspecialchars($guide['name']) ?></h3>
                                <div class="flex items-center">
                                    <span class="text-yellow-500"><?= str_repeat('★', $guide['rating']) ?></span>
                                    <span class="ml-2 text-sm text-gray-500"><?= $guide['total_bookings'] ?> tours completed</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="guide-profile.php?id=<?= $guide['user_id'] ?>" 
                               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                                View Profile
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?> 