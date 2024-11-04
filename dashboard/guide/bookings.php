<?php
require_once '../../config/config.php';
require_once '../../config/db.php';

// Check if user is logged in and is a guide
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Guide') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get all bookings for the guide
$stmt = $conn->prepare("
    SELECT 
        b.*,
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
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="px-4 py-5 sm:px-6">
                <h2 class="text-lg font-medium text-gray-900">Manage Bookings</h2>
                <p class="mt-1 text-sm text-gray-500">Review and manage your tour bookings</p>
            </div>
        </div>

        <!-- Booking Filters -->
        <div class="mt-4">
            <div class="bg-white shadow px-4 py-5 sm:px-6">
                <div class="flex flex-wrap gap-4">
                    <button onclick="filterBookings('all')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">
                        All
                    </button>
                    <button onclick="filterBookings('Pending')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                        Pending
                    </button>
                    <button onclick="filterBookings('Confirmed')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-green-100 text-green-800 hover:bg-green-200">
                        Confirmed
                    </button>
                    <button onclick="filterBookings('Completed')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-blue-100 text-blue-800 hover:bg-blue-200">
                        Completed
                    </button>
                    <button onclick="filterBookings('Cancelled')"
                        class="px-4 py-2 rounded-md text-sm font-medium bg-red-100 text-red-800 hover:bg-red-200">
                        Cancelled
                    </button>
                </div>
            </div>
        </div>

        <!-- Bookings List -->
        <div class="mt-4">
            <?php if (empty($bookings)): ?>
                <div class="bg-white shadow sm:rounded-lg p-6 text-center">
                    <p class="text-gray-500">No bookings found</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="bg-white shadow sm:rounded-lg booking-card" data-status="<?= $booking['status'] ?>">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">
                                            <?= htmlspecialchars($booking['destination_name']) ?>
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-500">
                                            <?= htmlspecialchars($booking['destination_location']) ?>
                                        </p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium
                                        <?php
                                        switch ($booking['status']) {
                                            case 'Confirmed':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            case 'Pending':
                                                echo 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'Cancelled':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'Completed':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                        }
                                        ?>">
                                        <?= ucfirst($booking['status']) ?>
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <!-- Tourist Information -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500">Tourist Details</h4>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-900">
                                                Name: <?= htmlspecialchars($booking['tourist_name']) ?>
                                            </p>
                                            <p class="text-sm text-gray-900">
                                                Email: <?= htmlspecialchars($booking['tourist_email']) ?>
                                            </p>
                                            <?php if ($booking['tourist_contact']): ?>
                                                <p class="text-sm text-gray-900">
                                                    Contact: <?= htmlspecialchars($booking['tourist_contact']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Booking Details -->
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-500">Booking Details</h4>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-900">
                                                Date: <?= date('F j, Y', strtotime($booking['booking_date'])) ?>
                                            </p>
                                            <p class="text-sm text-gray-900">
                                                Amount: $<?= number_format($booking['amount'], 2) ?>
                                            </p>
                                            <p class="text-sm text-gray-900">
                                                Booked on: <?= date('M d, Y', strtotime($booking['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-6 flex justify-end space-x-3">
                                    <!-- In the buttons section of both files -->
                                    <?php if ($booking['status'] === 'Pending'): ?>
                                        <button onclick="acceptBooking(<?= $booking['booking_id'] ?>)"
                                            class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium hover:bg-green-200">
                                            Accept
                                        </button>
                                        <button onclick="declineBooking(<?= $booking['booking_id'] ?>)"
                                            class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-medium hover:bg-red-200">
                                            Decline
                                        </button>
                                    <?php elseif ($booking['status'] === 'Confirmed'): ?>
                                        <button onclick="completeBooking(<?= $booking['booking_id'] ?>)"
                                            class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium hover:bg-blue-200">
                                            Mark as Completed
                                        </button>
                                    <?php endif; ?>
                                    <a href="messages.php?tourist_id=<?= $booking['tourist_id'] ?>"
                                        class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                        Message Tourist
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function filterBookings(status) {
        const cards = document.querySelectorAll('.booking-card');
        cards.forEach(card => {
            if (status === 'all') {
                card.style.display = 'block';
            } else {
                card.style.display = card.dataset.status === status ? 'block' : 'none';
            }
        });
    }

    // Update this section in both dashboard/guide/index.php and dashboard/guide/bookings.php

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
</script>

<?php include '../../includes/footer.php'; ?>