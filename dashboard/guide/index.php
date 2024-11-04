<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a guide
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Get guide data
$db = new Database();
$conn = $db->getConnection();

// Get guide details with ratings
$stmt = $conn->prepare("
    SELECT u.*, 
           COUNT(DISTINCT b.booking_id) as total_bookings,
           COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_tours,
           COALESCE(AVG(r.rating), 0) as average_rating
    FROM users u
    LEFT JOIN bookings b ON u.user_id = b.guide_id
    LEFT JOIN reviews r ON u.user_id = r.guide_id
    WHERE u.user_id = ? AND u.role = 'Guide'
    GROUP BY u.user_id
");
$stmt->execute([$_SESSION['user_id']]);
$guide = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent bookings with tourist details
$stmt = $conn->prepare("
    SELECT b.*, 
           d.name as destination_name, 
           d.location as destination_location,
           u.name as tourist_name,
           u.email as tourist_email,
           u.contact_info as tourist_contact
    FROM bookings b 
    JOIN destinations d ON b.destination_id = d.destination_id 
    JOIN users u ON b.tourist_id = u.user_id 
    WHERE b.guide_id = ?
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get earnings overview
$stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN b.status = 'completed' THEN b.amount ELSE 0 END), 0) as total_earnings,
        COALESCE(SUM(CASE WHEN b.status = 'pending' THEN b.amount ELSE 0 END), 0) as pending_earnings,
        COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as pending_bookings,
        COUNT(CASE WHEN b.status = 'completed' THEN 1 END) as completed_bookings
    FROM bookings b
    WHERE b.guide_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$earnings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get unread messages count
$stmt = $conn->prepare("
    SELECT COUNT(*) as unread_count 
    FROM messages 
    WHERE receiver_id = ? AND read_status = 'unread'
");
$stmt->execute([$_SESSION['user_id']]);
$messages = $stmt->fetch(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold"><?php
                        $hour = date('H');
                        if ($hour < 12) {
                            echo "Good morning, ";
                        } elseif ($hour < 17) {
                            echo "Good afternoon, ";
                        } else {
                            echo "Good evening, ";
                        }
                        echo htmlspecialchars($guide['name']);
                    ?></h1>
                    <p class="mt-2 text-blue-100">Your current rating 
                        <span class="text-yellow-400">
                            <?= str_repeat('★', round($guide['average_rating'])) ?>
                            <?= str_repeat('☆', 5 - round($guide['average_rating'])) ?>
                        </span>
                        (<?= number_format($guide['average_rating'], 1) ?>)
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xl font-semibold">Total Tours <?= $guide['total_bookings'] ?></p>
                    <p class="text-sm text-blue-100">Completed <?= $guide['completed_tours'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Earnings Card -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Earnings</dt>
                                <dd class="text-lg font-semibold text-gray-900">
                                    $<?= number_format((float)$earnings['total_earnings'], 2) ?>
                                </dd>
                                <dd class="text-xs text-gray-500">
                                    Pending: $<?= number_format((float)$earnings['pending_earnings'], 2) ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Bookings -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending Bookings</dt>
                                <dd class="text-lg font-semibold text-gray-900">
                                    <?= $earnings['pending_bookings'] ?>
                                </dd>
                                <dd class="text-xs text-gray-500">
                                    Completed: <?= $earnings['completed_bookings'] ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="bookings.php" class="font-medium text-blue-600 hover:text-blue-900">View all bookings</a>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Unread Messages</dt>
                                <dd class="text-lg font-semibold text-gray-900">
                                    <?= $messages['unread_count'] ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="messages.php" class="font-medium text-blue-600 hover:text-blue-900">Open messages</a>
                    </div>
                </div>
            </div>
            <!-- Destinations -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">My Destinations</dt>
                                <dd class="text-lg font-semibold text-gray-900">
                                    <?= $destinations['total_count'] ?? 0 ?>
                                </dd>
                                <dd class="text-xs text-gray-500">
                                    Active Tours: <?= $destinations['active_count'] ?? 0 ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-5 py-3">
                    <div class="text-sm">
                        <a href="destinations.php" class="font-medium text-blue-600 hover:text-blue-900">Manage destinations</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="mt-8">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">Recent Booking Requests</h2>
                <a href="calendar.php" class="text-sm font-medium text-blue-600 hover:text-blue-900">
                    View Calendar →
                </a>
            </div>
            <div class="mt-4 bg-white shadow-sm rounded-lg overflow-hidden">
                <?php if (empty($recent_bookings)): ?>
                    <div class="p-4 text-gray-500 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2">No booking requests yet.</p>
                    </div>
                <?php else: ?>
                    <ul class="divide-y divide-gray-200">
                        <?php foreach ($recent_bookings as $booking): ?>
                            <li class="p-4 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-lg font-medium text-blue-600">
                                            <?= htmlspecialchars($booking['destination_name']) ?>
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Tourist: <?= htmlspecialchars($booking['tourist_name']) ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?= htmlspecialchars($booking['destination_location']) ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            <button onclick="acceptBooking(<?= $booking['booking_id'] ?>)" 
                                                    class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium hover:bg-green-200">
                                                Accept
                                            </button>
                                            <button onclick="declineBooking(<?= $booking['booking_id'] ?>)"
                                                    class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium hover:bg-red-200">
                                                Decline
                                            </button>
                                        <?php else: ?>
                                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                                                <?php
                                                switch($booking['status']) {
                                                    case 'confirmed':
                                                        echo 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'completed':
                                                        echo 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'cancelled':
                                                        echo 'bg-red-100 text-red-800';
                                                        break;
                                                }
                                                ?>">
                                                <?= ucfirst($booking['status']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="text-sm text-gray-500">
                                            <?= date('M d, Y', strtotime($booking['booking_date'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Booking Action Modal -->
<div id="bookingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modalTitle">Confirm Action</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500" id="modalMessage"></p>
            </div>
            <div class="items-center px-4 py-3">
                <button id="modalConfirm" class="px-4 py-2 bg-blue-500 text-white text-base font-medium rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    Confirm
                </button>
                <button onclick="closeModal()" class="ml-3 px-4 py-2 bg-gray-100 text-gray-700 text-base font-medium rounded-md shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>

function acceptBooking(bookingId) {
    if (confirm('Are you sure you want to accept this booking?')) {
        window.location.href = `booking-action.php?action=accept&id=${bookingId}`;
    }
}

function declineBooking(bookingId) {
    if (confirm('Are you sure you want to decline this booking?')) {
        window.location.href = `booking-action.php?action=decline&id=${bookingId}`;
    }
}

function completeBooking(bookingId) {
    if (confirm('Are you sure you want to mark this booking as completed?')) {
        window.location.href = `booking-action.php?action=complete&id=${bookingId}`;
    }
}

function showModal(title, message, confirmCallback) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMessage').textContent = message;
    document.getElementById('modalConfirm').onclick = confirmCallback;
    document.getElementById('bookingModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('bookingModal').classList.add('hidden');
}
</script>

<?php include '../../includes/footer.php'; ?> 